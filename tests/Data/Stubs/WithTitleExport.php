<?php

namespace greenex\Excel\Tests\Data\Stubs;

use greenex\Excel\Concerns\WithTitle;
use greenex\Excel\Concerns\Exportable;

class WithTitleExport implements WithTitle
{
    use Exportable;

    /**
     * @return string
     */
    public function title(): string
    {
        return 'given-title';
    }
}
