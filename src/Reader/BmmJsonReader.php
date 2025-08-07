<?php

namespace OpenEHR\Tools\CodeGen\Reader;

use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSchema;
use RuntimeException;

class BmmJsonReader extends AbstractReader
{

    const string DIR = __READER_DIR__ . DIRECTORY_SEPARATOR . 'BMM' . DIRECTORY_SEPARATOR;

    public function read(string $filename): void
    {
        if (!str_ends_with($filename, '.bmm.json')) {
            $filename .= '.bmm.json';
        }
        $filename = self::DIR . $filename;
        if (!is_readable($filename) || !is_file($filename)) {
            throw new RuntimeException("File missing or not readable: $filename.");
        }
        self::log('Reading [%s] filename...', $filename);
        $jsonContent = file_get_contents($filename);
        if ($jsonContent === false) {
            throw new RuntimeException("Failed to read file: {$filename}");
        }
        $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);

        self::log("Deserializing to BMM objects...");
        $schema = BmmSchema::fromArray($data);
        self::log("  Read %d BMM Classes from %s.", $schema->classDefinitions->count(), $schema->getSchemaId());
        $this->files->add($schema);
    }
}
