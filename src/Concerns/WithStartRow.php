<?php

namespace greenex\Excel\Concerns;

interface WithStartRow
{
    /**
     * @return int
     */
    public function startRow(): int;
}
