<?php

namespace greenex\Excel\Tests\Data\Stubs;

use Illuminate\Database\Query\Builder;
use greenex\Excel\Concerns\FromQuery;
use greenex\Excel\Events\BeforeSheet;
use greenex\Excel\Concerns\Exportable;
use greenex\Excel\Concerns\WithEvents;
use greenex\Excel\Concerns\WithMapping;
use greenex\Excel\Tests\Data\Stubs\Database\User;

class FromUsersQueryExportWithMapping implements FromQuery, WithMapping, WithEvents
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
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class   => function (BeforeSheet $event) {
                $event->sheet->chunkSize(10);
            },
        ];
    }

    /**
     * @param User $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            'name' => $row->name,
        ];
    }
}
