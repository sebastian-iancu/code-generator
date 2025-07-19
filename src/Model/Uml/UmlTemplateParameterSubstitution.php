<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use SimpleXMLElement;

class UmlTemplateParameterSubstitution implements CollectableInterface
{

    use CollectableTrait;
    use ConsoleTrait;

    public readonly string $id;
    public readonly string $name;
    public readonly UmlTypeReference $actual;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (new UmlTypeReference($xmlNode, (string)$xmlNode['formal']))->name;
        $this->actual = new UmlTypeReference($xmlNode, (string)$xmlNode['actual']);

        self::log('  ParameterSubstitution [%s] as [%s] was read.', $this->name, $this->actual->name);
    }

}
