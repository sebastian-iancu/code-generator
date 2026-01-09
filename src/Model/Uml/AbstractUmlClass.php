<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use SimpleXMLElement;

class AbstractUmlClass implements CollectableInterface
{

    use CollectableTrait;
    use ConsoleTrait;

    public readonly string $id;
    public readonly string $name;
    public readonly string $description;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];
        $this->description = (string)$xmlNode->ownedComment['body'];
    }
}
