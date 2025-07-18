<?php

namespace OpenEHR\Tools\CodeGen\Reader;

use OpenEHR\Tools\CodeGen\Model\UMLFile;
use RuntimeException;

class XmiReader extends AbstractReader
{

    const string DIR = __READER_DIR__ . DIRECTORY_SEPARATOR . 'XMI' . DIRECTORY_SEPARATOR;

    public function read(string $filename): void
    {
        if (!str_ends_with($filename, '.xmi')) {
            $filename .= '.xmi';
        }
        self::log('Reading [%s] filename...', $filename);
        if (!is_readable(self::DIR . $filename) || !is_file(self::DIR . $filename)) {
            throw new RuntimeException("File missing or not readable: $filename in " . self::DIR . ".");
        }
        try {
            $xml = new \SimpleXMLElement(self::DIR . $filename, dataIsURL: true);
        } catch (\Exception $e) {
            throw new RuntimeException("libxml errors in $filename: {$e->getMessage()}.", previous: $e);
        }

        $umlFile = new UMLFile($xml, $filename);
        $this->files->add($umlFile, $umlFile->umlPackage->id);
    }


}
