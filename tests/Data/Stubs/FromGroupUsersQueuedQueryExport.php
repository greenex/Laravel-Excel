<?php

namespace greenex\Excel\Tests\Data\Stubs;

use Illuminate\Database\Query\Builder;
use greenex\Excel\Concerns\FromQuery;
use greenex\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use greenex\Excel\Concerns\WithMapping;
use greenex\Excel\Concerns\WithCustomChunkSize;
use greenex\Excel\Tests\Data\Stubs\Database\Group;

class FromGroupUsersQueuedQueryExport implements FromQuery, WithCustomChunkSize, ShouldQueue, WithMapping
{
    use Exportable;

    /**
     * @return Builder
     */
    public function query()
    {
        return Group::first()->users();
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->name,
            $row->email,
        ];
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 10;
    }
}
