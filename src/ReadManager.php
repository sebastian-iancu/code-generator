<?php

namespace OpenEHR\Tools\CodeGen;

use OpenEHR\Tools\CodeGen\Helper\Collection;
use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Model\UMLFile;

class ReadManager {

    use ConsoleTrait;

    public function __construct(
        public readonly Collection $umlFiles = new Collection(),
        public readonly string     $readerDir = __READER_DIR__,
    ) {
    }

    public function read(string $fileName): void {
        $umlFile = new UMLFile($this->readerDir . DIRECTORY_SEPARATOR . $fileName);
        $this->umlFiles->add($umlFile);
        $this->umlFiles->add($umlFile, $umlFile->umlPackage->id);
    }


}
