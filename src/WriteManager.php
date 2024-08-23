<?php

namespace OpenEHR\Tools\CodeGen;

use OpenEHR\Tools\CodeGen\Writer\AbstractWriter;

class WriteManager
{

    /** @var AbstractWriter[] */
    protected array $writers;

    public function __construct(
        protected readonly ReadManager $reader,
        public readonly string         $writerDir = __WRITER_DIR__,
    )
    {
    }

    public function addWriter(AbstractWriter $writer): void
    {
        $writer->setReader($this->reader);
        $writer->setDir($this->writerDir);
        $this->writers[] = $writer;
    }

    public function write(): void
    {
        foreach ($this->writers as $writer) {
            $writer->write();
        }
    }

}
