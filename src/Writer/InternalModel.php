<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use JsonException;

class InternalModel extends AbstractWriter
{

    public function __construct(
        public readonly string $file = 'all.json',
    )
    {
    }

    public function setDir(string $dir): void
    {
        $dir .= DIRECTORY_SEPARATOR . 'InternalModel';
        parent::setDir($dir);
    }


    /**
     * @throws JsonException
     */
    public function write(): void
    {
        $filename = $this->dir . DIRECTORY_SEPARATOR . $this->file;
        self::log('Writing to [%s] filename.', $filename);
        $bytes = file_put_contents($filename, json_encode($this->reader, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        self::log('  Wrote %s bytes to %s file.', $bytes, $filename);
    }
}
