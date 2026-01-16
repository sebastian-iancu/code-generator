<?php

namespace OpenEHR\Tools\CodeGen\Writer\Formatter;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmPackage;

readonly class AsciidocPlantUml
{

    private bool $legacyFormat;
    private PlantUml $plantUml;

    public function __construct(bool $legacyFormat = false)
    {
        $this->legacyFormat = $legacyFormat;
        $this->plantUml = new PlantUml($this->legacyFormat);
    }

    public function format(AbstractBmmClass|BmmPackage $bmmItem, string $prefix): string
    {
        $content = $this->plantUml->format($bmmItem, $prefix);
        // notice: for UML with hyperlinks need "opts=inline", thus : [plantuml,$bmmItem->name,format=svg,opts=inline]
        return <<<ASCIIDOC
[plantuml,$bmmItem->name,format=svg]
-----
$content
-----
ASCIIDOC;

    }
}
