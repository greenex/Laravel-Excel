<?php

namespace greenex\Excel\Concerns;

use greenex\Excel\Row;

interface OnEachRow
{
    /**
     * @param Row $row
     */
    public function onRow(Row $row);
}
