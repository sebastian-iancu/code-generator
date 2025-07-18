<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Reader\AbstractReader;
use RuntimeException;

abstract class AbstractWriter
{

    use ConsoleTrait;

    public const string DIR = __WRITER_DIR__ . DIRECTORY_SEPARATOR;

    protected AbstractReader $reader;

    public function setReader(AbstractReader $reader): void
    {
        $this->reader = $reader;
    }

    public function assureOutputDir(): void
    {
        if (!is_dir(static::DIR)) {
            if (is_file(static::DIR) || is_link(static::DIR)) {
                throw new RuntimeException(sprintf('The "%s" already exists but is not a directory.', static::DIR));
            }
            if (!@mkdir(static::DIR, 0777, true) && !is_dir(static::DIR)) {
                throw new RuntimeException(sprintf('Directory "%s" does not exist and cannot be created.', static::DIR));
            }
        }
        if (!is_writable(static::DIR)) {
            throw new RuntimeException(sprintf('Directory "%s" is not writable.', static::DIR));

        }
    }

    abstract public function write(): void;

}
