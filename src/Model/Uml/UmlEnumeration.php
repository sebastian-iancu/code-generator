<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use SimpleXMLElement;

class UmlEnumeration extends AbstractUmlClass
{

    /** @var array<array<string, mixed>> */
    public readonly array $enumerations;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        parent::__construct($xmlNode);
        $enumerations = [];
        foreach ($xmlNode->ownedLiteral as $node) {
            $enumerations[] = [
                'name' => (string)$node['name'],
                'description' => (string)$node->ownedComment['body'],
            ];
        }
        $this->enumerations = $enumerations;

        self::log('  Enumerations [%s] with [%s] values was read.', $this->name, count($this->enumerations));
    }

}
