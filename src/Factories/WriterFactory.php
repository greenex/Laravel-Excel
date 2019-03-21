<?php

namespace greenex\Excel\Factories;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use greenex\Excel\Concerns\WithCharts;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use greenex\Excel\Concerns\MapsCsvSettings;
use greenex\Excel\Concerns\WithMultipleSheets;
use greenex\Excel\Concerns\WithCustomCsvSettings;
use greenex\Excel\Concerns\WithPreCalculateFormulas;

class WriterFactory
{
    use MapsCsvSettings;

    /**
     * @param string      $writerType
     * @param Spreadsheet $spreadsheet
     * @param object      $export
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return IWriter
     */
    public static function make(string $writerType, Spreadsheet $spreadsheet, $export): IWriter
    {
        $writer = IOFactory::createWriter($spreadsheet, $writerType);

        if (static::includesCharts($export)) {
            $writer->setIncludeCharts(true);
        }

        if ($writer instanceof Html && $export instanceof WithMultipleSheets) {
            $writer->writeAllSheets();
        }

        if ($writer instanceof Csv) {
            static::applyCsvSettings(config('excel2.exports.csv', []));

            if ($export instanceof WithCustomCsvSettings) {
                static::applyCsvSettings($export->getCsvSettings());
            }

            $writer->setDelimiter(static::$delimiter);
            $writer->setEnclosure(static::$enclosure);
            $writer->setLineEnding(static::$lineEnding);
            $writer->setUseBOM(static::$useBom);
            $writer->setIncludeSeparatorLine(static::$includeSeparatorLine);
            $writer->setExcelCompatibility(static::$excelCompatibility);
        }

        // Calculation settings
        $writer->setPreCalculateFormulas(
            $export instanceof WithPreCalculateFormulas
                ? true
                : config('excel2.exports.pre_calculate_formulas', false)
        );

        return $writer;
    }

    /**
     * @param $export
     *
     * @return bool
     */
    private static function includesCharts($export): bool
    {
        if ($export instanceof WithCharts) {
            return true;
        }

        if ($export instanceof WithMultipleSheets) {
            foreach ($export->sheets() as $sheet) {
                if ($sheet instanceof WithCharts) {
                    return true;
                }
            }
        }

        return false;
    }
}
