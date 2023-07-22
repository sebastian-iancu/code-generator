<?php

namespace OpenEHR\Tools\CodeGen\Model;

use SimpleXMLElement;

class UMLGeneralization extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly ?TypeReference $general;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        if (isset($xmlNode['general'])) {
            $this->general = new TypeReference($xmlNode, (string)$xmlNode['general']);
        } elseif ($xmlNode->general) {
            $this->general = new TypeReference($xmlNode->general);
        } else {
            $this->log("WARNING: Generalization without [general] at $this->id.");
            $this->general = new TypeReference();
        }
        $this->name = $this->general->name;

        $this->log('  Generalization [%s] was read.', $this->name);
    }

}
