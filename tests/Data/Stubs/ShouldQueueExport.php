<?php

namespace greenex\Excel\Tests\Data\Stubs;

use greenex\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use greenex\Excel\Concerns\WithMultipleSheets;

class ShouldQueueExport implements WithMultipleSheets, ShouldQueue
{
    use Exportable;

    /**
     * @return SheetWith100Rows[]
     */
    public function sheets(): array
    {
        return [
            new SheetWith100Rows('A'),
            new SheetWith100Rows('B'),
            new SheetWith100Rows('C'),
        ];
    }
}
