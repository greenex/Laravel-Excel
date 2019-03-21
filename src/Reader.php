<?php

namespace greenex\Excel;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use greenex\Excel\Events\AfterImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use greenex\Excel\Concerns\WithEvents;
use greenex\Excel\Events\BeforeImport;
use greenex\Excel\Files\TemporaryFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use greenex\Excel\Factories\ReaderFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use greenex\Excel\Concerns\WithChunkReading;
use greenex\Excel\Files\TemporaryFileFactory;
use greenex\Excel\Concerns\SkipsUnknownSheets;
use greenex\Excel\Concerns\WithMultipleSheets;
use greenex\Excel\Concerns\WithCustomValueBinder;
use greenex\Excel\Concerns\WithCalculatedFormulas;
use greenex\Excel\Transactions\TransactionHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use greenex\Excel\Exceptions\SheetNotFoundException;
use greenex\Excel\Exceptions\NoTypeDetectedException;

class Reader
{
    use DelegatedMacroable, HasEventBus;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var object[]
     */
    protected $sheetImports = [];

    /**
     * @var TemporaryFile
     */
    protected $currentFile;

    /**
     * @var TemporaryFileFactory
     */
    protected $temporaryFileFactory;

    /**
     * @var TransactionHandler
     */
    protected $transaction;

    /**
     * @param TemporaryFileFactory $temporaryFileFactory
     * @param TransactionHandler   $transaction
     */
    public function __construct(TemporaryFileFactory $temporaryFileFactory, TransactionHandler $transaction)
    {
        $this->setDefaultValueBinder();

        $this->transaction          = $transaction;
        $this->temporaryFileFactory = $temporaryFileFactory;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string|null         $readerType
     * @param string|null         $disk
     *
     * @throws Exception
     * @throws NoTypeDetectedException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Foundation\Bus\PendingDispatch|$this
     */
    public function read($import, $filePath, string $readerType = null, string $disk = null)
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);

        if ($import instanceof WithChunkReading) {
            return (new ChunkReader)->read($import, $reader, $this->currentFile);
        }

        $this->beforeReading($import, $reader);

        ($this->transaction)(function () use ($import) {
            foreach ($this->sheetImports as $index => $sheetImport) {
                if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                    $sheet->import($sheetImport, $sheet->getStartRow($sheetImport));
                    $sheet->disconnect();
                }
            }
        });

        $this->afterReading($import);

        return $this;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string              $readerType
     * @param string|null         $disk
     *
     * @throws Exceptions\SheetNotFoundException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws NoTypeDetectedException
     * @return array
     */
    public function toArray($import, $filePath, string $readerType, string $disk = null): array
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);
        $this->beforeReading($import, $reader);

        $sheets = [];
        foreach ($this->sheetImports as $index => $sheetImport) {
            $calculatesFormulas = $sheetImport instanceof WithCalculatedFormulas;
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets[$index] = $sheet->toArray($sheetImport, $sheet->getStartRow($sheetImport), null, $calculatesFormulas);
                $sheet->disconnect();
            }
        }

        $this->afterReading($import);

        return $sheets;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string              $readerType
     * @param string|null         $disk
     *
     * @throws Exceptions\SheetNotFoundException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws NoTypeDetectedException
     * @return Collection
     */
    public function toCollection($import, $filePath, string $readerType, string $disk = null): Collection
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);
        $this->beforeReading($import, $reader);

        $sheets = new Collection();
        foreach ($this->sheetImports as $index => $sheetImport) {
            $calculatesFormulas = $sheetImport instanceof WithCalculatedFormulas;
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets->put($index, $sheet->toCollection($sheetImport, $sheet->getStartRow($sheetImport), null, $calculatesFormulas));
                $sheet->disconnect();
            }
        }

        $this->afterReading($import);

        return $sheets;
    }

    /**
     * @return object
     */
    public function getDelegate()
    {
        return $this->spreadsheet;
    }

    /**
     * @return $this
     */
    public function setDefaultValueBinder(): self
    {
        Cell::setValueBinder(
            app(config('excel2.value_binder.default', DefaultValueBinder::class))
        );

        return $this;
    }

    /**
     * @param $import
     * @param $sheetImport
     * @param $index
     *
     * @throws SheetNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return Sheet|null
     */
    protected function getSheet($import, $sheetImport, $index)
    {
        try {
            return Sheet::make($this->spreadsheet, $index);
        } catch (SheetNotFoundException $e) {
            if ($import instanceof SkipsUnknownSheets) {
                $import->onUnknownSheet($index);

                return null;
            }

            if ($sheetImport instanceof SkipsUnknownSheets) {
                $sheetImport->onUnknownSheet($index);

                return null;
            }

            throw $e;
        }
    }

    /**
     * @param object  $import
     * @param IReader $reader
     *
     * @return array
     */
    private function buildSheetImports($import, IReader $reader): array
    {
        $sheetImports = [];
        if ($import instanceof WithMultipleSheets) {
            $sheetImports = $import->sheets();

            // When only sheet names are given and the reader has
            // an option to load only the selected sheets.
            if (
                method_exists($reader, 'setLoadSheetsOnly')
                && count(array_filter(array_keys($sheetImports), 'is_numeric')) === 0
            ) {
                $reader->setLoadSheetsOnly(array_keys($sheetImports));
            }
        }

        return $sheetImports;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string|null         $readerType
     * @param string              $disk
     *
     * @throws InvalidArgumentException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws NoTypeDetectedException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @return IReader
     */
    private function getReader($import, $filePath, string $readerType = null, string $disk = null): IReader
    {
        $shouldQueue = $import instanceof ShouldQueue;
        if ($shouldQueue && !$import instanceof WithChunkReading) {
            throw new InvalidArgumentException('ShouldQueue is only supported in combination with WithChunkReading.');
        }

        if ($import instanceof WithEvents) {
            $this->registerListeners($import->registerEvents());
        }

        if ($import instanceof WithCustomValueBinder) {
            Cell::setValueBinder($import);
        }

        $temporaryFile     = $shouldQueue ? $this->temporaryFileFactory->make() : $this->temporaryFileFactory->makeLocal();
        $this->currentFile = $temporaryFile->copyFrom(
            $filePath,
            $disk
        );

        return ReaderFactory::make(
            $import,
            $this->currentFile,
            $readerType
        );
    }

    /**
     * @param object  $import
     * @param IReader $reader
     *
     * @throws Exception
     */
    private function beforeReading($import, IReader $reader)
    {
        $this->sheetImports = $this->buildSheetImports($import, $reader);

        $this->spreadsheet = $reader->load(
            $this->currentFile->getLocalPath()
        );

        // When no multiple sheets, use the main import object
        // for each loaded sheet in the spreadsheet
        if (!$import instanceof WithMultipleSheets) {
            $this->sheetImports = array_fill(0, $this->spreadsheet->getSheetCount(), $import);
        }

        $this->raise(new BeforeImport($this, $import));
    }

    /**
     * @param object $import
     */
    private function afterReading($import)
    {
        $this->raise(new AfterImport($this, $import));

        $this->garbageCollect();
    }

    /**
     * Garbage collect.
     */
    private function garbageCollect()
    {
        $this->setDefaultValueBinder();

        // Force garbage collecting
        unset($this->sheetImports, $this->spreadsheet);

        $this->currentFile->delete();
    }
}
