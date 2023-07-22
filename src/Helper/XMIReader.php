<?php

namespace OpenEHR\Tools\CodeGen\Helper;

use OpenEHR\Tools\CodeGen\Model\UMLFile;

class XMIReader {

    use ConsoleTrait;

    public function __construct(
        public readonly Collection $umlFiles = new Collection(),
        public readonly string     $readerDir = __READER_DIR__,
    ) {
    }

    public function read(string $fileName): void {
        $this->log('Reading [%s] filename...', $fileName);
        $umlFile = new UMLFile($this->readerDir . DIRECTORY_SEPARATOR . $fileName);
        $this->log('  UML file [id:%s, name:%s] was read.', $umlFile->id, $umlFile->name);
        $this->umlFiles->add($umlFile);
        $this->umlFiles->add($umlFile, $umlFile->umlPackage->id);
        $this->log('  Added [%s] alias.', $umlFile->umlPackage->id);
    }


}
