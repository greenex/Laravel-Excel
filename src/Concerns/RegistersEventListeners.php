<?php

namespace greenex\Excel\Concerns;

use greenex\Excel\Events\AfterSheet;
use greenex\Excel\Events\AfterImport;
use greenex\Excel\Events\BeforeSheet;
use greenex\Excel\Events\BeforeExport;
use greenex\Excel\Events\BeforeImport;
use greenex\Excel\Events\BeforeWriting;

trait RegistersEventListeners
{
    /**
     * @return array
     */
    public function registerEvents(): array
    {
        $listeners = [];

        if (method_exists($this, 'beforeExport')) {
            $listeners[BeforeExport::class] = [static::class, 'beforeExport'];
        }

        if (method_exists($this, 'beforeWriting')) {
            $listeners[BeforeWriting::class] = [static::class, 'beforeWriting'];
        }

        if (method_exists($this, 'beforeImport')) {
            $listeners[BeforeImport::class] = [static::class, 'beforeImport'];
        }

        if (method_exists($this, 'afterImport')) {
            $listeners[AfterImport::class] = [static::class, 'afterImport'];
        }

        if (method_exists($this, 'beforeSheet')) {
            $listeners[BeforeSheet::class] = [static::class, 'beforeSheet'];
        }

        if (method_exists($this, 'afterSheet')) {
            $listeners[AfterSheet::class] = [static::class, 'afterSheet'];
        }

        return $listeners;
    }
}
