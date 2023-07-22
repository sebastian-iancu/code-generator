<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Helper\XMIReader;

abstract class AbstractWriter
{

    use ConsoleTrait;

    protected string $dir;
    protected XMIReader $reader;

    public function setDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        $this->dir = realpath($dir) ?: $dir;
    }

    public function setReader(XMIReader $reader): void
    {
        $this->reader = $reader;
    }

    abstract public function write(): void;

}
