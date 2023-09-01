<?php

namespace OpenEHR\Tools\CodeGen\Model;

use SimpleXMLElement;

class UMLTemplateParameterSubstitution extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly TypeReference $actual;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (new TypeReference($xmlNode, (string)$xmlNode['formal']))->name;
        $this->actual = new TypeReference($xmlNode, (string)$xmlNode['actual']);

        self::log('  ParameterSubstitution [%s] as [%s] was read.', $this->name, $this->actual->name);
    }

}
