<?php

namespace greenex\Excel\Tests\Data\Stubs;

use greenex\Excel\Concerns\Exportable;
use greenex\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Collection;
use greenex\Excel\Concerns\FromCollection;
use greenex\Excel\Tests\Data\Stubs\Database\User;

class EloquentCollectionWithMappingExport implements FromCollection, WithMapping
{
    use Exportable;

    /**
     * @return Collection
     */
    public function collection()
    {
        return collect([
            new User([
                'firstname' => 'Patrick',
                'lastname'  => 'Brouwers',
            ]),
        ]);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function map($user): array
    {
        return [
            $user->firstname,
            $user->lastname,
        ];
    }
}
