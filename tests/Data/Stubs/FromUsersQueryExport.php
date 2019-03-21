<?php

namespace greenex\Excel\Tests\Data\Stubs;

use Illuminate\Database\Query\Builder;
use greenex\Excel\Concerns\FromQuery;
use greenex\Excel\Concerns\Exportable;
use greenex\Excel\Concerns\WithCustomChunkSize;
use greenex\Excel\Tests\Data\Stubs\Database\User;

class FromUsersQueryExport implements FromQuery, WithCustomChunkSize
{
    use Exportable;

    /**
     * @return Builder
     */
    public function query()
    {
        return User::query();
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 10;
    }
}
