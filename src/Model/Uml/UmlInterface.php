<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use SimpleXMLElement;

class UmlInterface extends UmlClass
{

    public readonly bool $isInterface;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        parent::__construct($xmlNode);
        $this->isInterface = true;

        self::log('  Interface [%s] was read.', $this->name);
    }


}
