<?php

namespace greenex\Excel\Tests\Data\Stubs;

use greenex\Excel\Events\AfterSheet;
use greenex\Excel\Events\AfterImport;
use greenex\Excel\Events\BeforeSheet;
use greenex\Excel\Concerns\Importable;
use greenex\Excel\Concerns\WithEvents;
use greenex\Excel\Events\BeforeImport;

class ImportWithEvents implements WithEvents
{
    use Importable;

    /**
     * @var callable
     */
    public $beforeImport;

    /**
     * @var callable
     */
    public $beforeSheet;

    /**
     * @var callable
     */
    public $afterSheet;

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => $this->beforeImport ?? function () {
            },
            AfterImport::class => $this->afterImport ?? function () {
            },
            BeforeSheet::class => $this->beforeSheet ?? function () {
            },
            AfterSheet::class => $this->afterSheet ?? function () {
            },
        ];
    }
}
