<?php

namespace OpenEHR\Tools\CodeGen\Reader;

use OpenEHR\Tools\CodeGen\Helper\Collection;
use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;

abstract class AbstractReader
{

    use ConsoleTrait;

    public function __construct(
        public readonly Collection $files = new Collection(),
    )
    {
    }

    abstract public function read(string $filename): void;

}
