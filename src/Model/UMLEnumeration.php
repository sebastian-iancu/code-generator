<?php

namespace OpenEHR\Tools\CodeGen\Model;

use SimpleXMLElement;

class UMLEnumeration extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly string $description;
    /** @var string[] */
    public readonly array $enumerations;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];
        $this->description = (string)$xmlNode->ownedComment['body'];
        $enumerations = [];
        foreach ($xmlNode->ownedLiteral as $node) {
            $enumerations[] = (string)$node['name'];
        }
        $this->enumerations = $enumerations;

        $this->log('  Enumerations [%s] with %s was read.', $this->name, count($this->enumerations));
    }

}
