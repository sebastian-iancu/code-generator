<?php

namespace OpenEHR\Tools\CodeGen\Writer;

class InternalModel extends AbstractWriter
{

    public function __construct(
        public readonly string $file = 'all.json',
    ) {
    }

    public function setDir(string $dir): void
    {
        $dir .= DIRECTORY_SEPARATOR . 'InternalModel';
        parent::setDir($dir);
    }


    public function write(): void
    {
        $filename = $this->dir . DIRECTORY_SEPARATOR . $this->file;
        $this->log('Writing to [%s] filename.', $filename);
        $bytes = file_put_contents($filename, json_encode($this->reader, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        $this->log('  Wrote %s bytes to %s file.', $bytes, $filename);
    }
}
