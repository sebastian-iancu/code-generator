<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Model\AbstractItem;
use RuntimeException;
use SimpleXMLElement;

class UmlFile extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly UmlPackage $umlPackage;

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
        $this->umlPackage = new UmlPackage($nodes[0]);
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
