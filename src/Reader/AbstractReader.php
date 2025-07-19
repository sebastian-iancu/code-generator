<?php

namespace OpenEHR\Tools\CodeGen\Reader;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Model\Collection;

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
