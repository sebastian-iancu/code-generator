<?php

namespace OpenEHR\Tools\CodeGen\Model;

use RuntimeException;
use SimpleXMLElement;

class UMLFile extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly UMLPackage $umlPackage;

    public function __construct(SimpleXMLElement $xmlNode, string $id)
    {
        $this->id = $id;
        $nodes = $xmlNode->xpath('//uml:Package[@xmi:type="uml:Package"]');
        if (!$nodes) {
            throw new RuntimeException("XMI errors in $this->id: main package not found.");
        }
        if (count($nodes) > 1) {
            self::log("WARNING: Found more then one UML Package in the $this->id file. This will only process the first one");
        }
        $this->umlPackage = new UMLPackage($nodes[0]);
        $this->name = $this->umlPackage->name;
    }


    public function getRelease(): string
    {
        return str_replace([
            $this->name . '-v',
            '.xmi'
        ], '', $this->id);
    }


}
