<?php

namespace OpenEHR\Tools\CodeGen\Model;

use SimpleXMLElement;

class UMLParameter extends AbstractAttribute
{

    public function __construct(SimpleXMLElement $xmlNode)
    {
        parent::__construct($xmlNode);
        self::log('  Parameter [%s] of [%s] type was read.', $this->name, $this->type->name);
    }

}
