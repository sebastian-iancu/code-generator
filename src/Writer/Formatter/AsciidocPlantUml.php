<?php

namespace OpenEHR\Tools\CodeGen\Writer\Formatter;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmPackage;

class AsciidocPlantUml
{

    private PlantUml $plantUml;

    public function __construct()
    {
        $this->plantUml = new PlantUml();
    }

    public function format(AbstractBmmClass|BmmPackage $bmmItem, string $prefix): string
    {
        $content = $this->plantUml->format($bmmItem, $prefix);
        return <<<ASCIIDOC
[plantuml,$bmmItem->name,format=svg,svg-type="inline"]
-----
$content
-----
ASCIIDOC;

    }
}
