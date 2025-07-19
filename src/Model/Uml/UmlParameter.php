<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use SimpleXMLElement;

class UmlParameter extends AbstractUmlAttribute
{

    public function __construct(SimpleXMLElement $xmlNode)
    {
        parent::__construct($xmlNode);
        self::log('  Parameter [%s] of [%s] type was read.', $this->name, $this->type->name);
    }

}
