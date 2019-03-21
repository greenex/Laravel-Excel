<?php

namespace greenex\Excel\Tests\Data\Stubs;

use Illuminate\Database\Eloquent\Model;
use greenex\Excel\Concerns\ToModel;
use greenex\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use greenex\Excel\Concerns\WithBatchInserts;
use greenex\Excel\Concerns\WithChunkReading;
use greenex\Excel\Tests\Data\Stubs\Database\Group;

class QueuedImport implements ShouldQueue, ToModel, WithChunkReading, WithBatchInserts
{
    use Importable;

    /**
     * @param array $row
     *
     * @return Model|null
     */
    public function model(array $row)
    {
        return new Group([
            'name' => $row[0],
        ]);
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 100;
    }
}
