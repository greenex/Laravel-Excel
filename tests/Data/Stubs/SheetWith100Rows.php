<?php

namespace greenex\Excel\Tests\Data\Stubs;

use greenex\Excel\Writer;
use Illuminate\Support\Collection;
use greenex\Excel\Tests\TestCase;
use greenex\Excel\Concerns\WithTitle;
use greenex\Excel\Concerns\Exportable;
use greenex\Excel\Concerns\WithEvents;
use greenex\Excel\Events\BeforeWriting;
use greenex\Excel\Concerns\FromCollection;
use greenex\Excel\Concerns\ShouldAutoSize;
use greenex\Excel\Concerns\RegistersEventListeners;

class SheetWith100Rows implements FromCollection, WithTitle, ShouldAutoSize, WithEvents
{
    use Exportable, RegistersEventListeners;

    /**
     * @var string
     */
    private $title;

    /**
     * @param string $title
     */
    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $collection = new Collection;
        for ($i = 0; $i < 100; $i++) {
            $row = new Collection();
            for ($j = 0; $j < 5; $j++) {
                $row[] = $this->title() . '-' . $i . '-' . $j;
            }

            $collection->push($row);
        }

        return $collection;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @param BeforeWriting $event
     */
    public static function beforeWriting(BeforeWriting $event)
    {
        TestCase::assertInstanceOf(Writer::class, $event->writer);
    }
}
