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

    public function assureOutputDir(string $dir = ''): void
    {
        $dir = $dir ?: static::DIR;
        if (!is_dir($dir)) {
            if (is_file($dir) || is_link($dir)) {
                throw new RuntimeException(sprintf('The "%s" already exists but is not a directory.', $dir));
            }
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory "%s" does not exist and cannot be created.', $dir));
            }
        }
        if (!is_writable($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" is not writable.', $dir));
        }
    }

    protected function writeFile(string $filename, string $content): void
    {
        $bytes = file_put_contents($filename, $content);
        self::log('  Wrote %s bytes to %s file.', $bytes, $filename);
    }

    abstract public function write(): void;

}
