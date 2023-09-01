<?php

namespace OpenEHR\Tools\CodeGen\Model;

use SimpleXMLElement;

class UMLConstraint extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly string $rule;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];
        if (isset($xmlNode->specification->body)) {
            $this->rule = $xmlNode->specification->body;
        } else {
            self::log("WARNING: Constraint $this->id is missing Rule body.");
            $this->rule = '';
        }

        self::log('  Constraint [%s] was read.', $this->name);
    }

}
