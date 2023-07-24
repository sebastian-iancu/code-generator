<?php

namespace OpenEHR\Tools\CodeGen\Model;

use SimpleXMLElement;

class UMLInterface extends UMLClass
{

    public readonly bool $isInterface;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        parent::__construct($xmlNode);
        $this->isInterface = true;

        $this->log('  Interface [%s] was read.', $this->name);
    }


}
