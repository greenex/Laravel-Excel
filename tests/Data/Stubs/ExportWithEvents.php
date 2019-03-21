<?php

namespace greenex\Excel\Tests\Data\Stubs;

use greenex\Excel\Events\AfterSheet;
use greenex\Excel\Events\BeforeSheet;
use greenex\Excel\Concerns\Exportable;
use greenex\Excel\Concerns\WithEvents;
use greenex\Excel\Events\BeforeExport;
use greenex\Excel\Events\BeforeWriting;

class ExportWithEvents implements WithEvents
{
    use Exportable;

    /**
     * @var callable
     */
    public $beforeExport;

    /**
     * @var callable
     */
    public $beforeWriting;

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
            BeforeExport::class  => $this->beforeExport ?? function () {
            },
            BeforeWriting::class => $this->beforeWriting ?? function () {
            },
            BeforeSheet::class   => $this->beforeSheet ?? function () {
            },
            AfterSheet::class    => $this->afterSheet ?? function () {
            },
        ];
    }
}
