<?php

namespace greenex\Excel\Tests\Concerns;

use greenex\Excel\Tests\TestCase;
use greenex\Excel\Concerns\FromArray;
use greenex\Excel\Concerns\Exportable;

class FromArrayTest extends TestCase
{
    /**
     * @test
     */
    public function can_export_from_array()
    {
        $export = new class implements FromArray {
            use Exportable;

            /**
             * @return array
             */
            public function array(): array
            {
                return [
                    ['test', 'test'],
                    ['test', 'test'],
                ];
            }
        };

        $response = $export->store('from-array-store.xlsx');

        $this->assertTrue($response);

        $contents = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/from-array-store.xlsx', 'Xlsx');

        $this->assertEquals($export->array(), $contents);
    }
}
