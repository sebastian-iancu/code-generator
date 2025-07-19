<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Model\AbstractItem;
use SimpleXMLElement;

class UmlGeneralization extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly UmlTypeReference $general;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        if (isset($xmlNode['general'])) {
            $this->general = new UmlTypeReference($xmlNode, (string)$xmlNode['general']);
        } elseif ($xmlNode->general) {
            $this->general = new UmlTypeReference($xmlNode->general);
        } else {
            self::log("WARNING: Generalization without [general] at $this->id.");
            $this->general = new UmlTypeReference();
        }
        $this->name = $this->general->name;

        self::log('  Generalization [%s] was read.', $this->name);
    }

}
