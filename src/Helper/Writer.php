<?php

namespace OpenEHR\Tools\CodeGen\Helper;

use OpenEHR\Tools\CodeGen\Writer\AbstractWriter;

class Writer {

    /** @var AbstractWriter[] */
    protected array $writers;

    public function __construct(
        protected readonly XMIReader $reader,
        public readonly string $writerDir = __WRITER_DIR__,
    ) {
    }

    public function addWriter(AbstractWriter $writer): void {
        $writer->setReader($this->reader);
        $writer->setDir($this->writerDir);
        $this->writers[] = $writer;
    }

    public function write(): void {
        foreach ($this->writers as $writer) {
            $writer->write();
        }
    }

}
