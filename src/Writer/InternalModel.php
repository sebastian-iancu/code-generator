<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use JsonException;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;

class InternalModel extends AbstractWriter
{

    public const string DIR = __WRITER_DIR__ . DIRECTORY_SEPARATOR . 'InternalModel' . DIRECTORY_SEPARATOR;

    public function __construct(
        public readonly string $filename = '',
    )
    {
    }


    /**
     * @throws JsonException
     */
    public function write(): void
    {
        $filename = $this->filename ?: implode('_and_', array_map(function (CollectableInterface $UMLFile) {
            return $UMLFile->getName();
        }, $this->reader->files->getArrayCopy()));
        $filename = self::DIR . $filename . '.internal.json';
        self::log('Writing to [%s] filename.', $filename);
        $bytes = file_put_contents($filename, json_encode($this->reader, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        self::log('  Wrote %s bytes to %s file.', $bytes, $filename);
    }
}
