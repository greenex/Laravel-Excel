<?php

namespace greenex\Excel\Concerns;

use Throwable;
use Illuminate\Support\Collection;
use greenex\Excel\Validators\Failure;

trait SkipsErrors
{
    /**
     * @var Failure[]
     */
    protected $errors = [];

    /**
     * @param Throwable $e
     */
    public function onError(Throwable $e)
    {
        $this->errors[] = $e;
    }

    /**
     * @return Throwable[]|Collection
     */
    public function errors(): Collection
    {
        return new Collection($this->errors);
    }
}
