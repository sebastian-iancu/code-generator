<?php

namespace OpenEHR\Tools\CodeGen\Model;

use OpenEHR\Tools\CodeGen\Helper\Collection;
use SimpleXMLElement;

class UMLOperation extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly string $description;
    public readonly Collection $umlParameters;
    public readonly TypeReference $return;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];
        $this->description = (string)$xmlNode->ownedComment['body'];
        // collect parameters
        $this->umlParameters = new Collection();
        foreach ($xmlNode->xpath("ownedParameter[@xmi:type='uml:Parameter' and (not(@direction) or not(@direction='return'))]") as $umlParameterNode) {
            $item = new UMLParameter($umlParameterNode);
            $this->umlParameters->add($item);
        }
        // detect return type
        $nodes = $xmlNode->xpath("ownedParameter[@xmi:type='uml:Parameter' and @direction='return']");
        $this->return = $nodes ? (new UMLParameter($nodes[0]))->type : new TypeReference(null, 'void');

        $this->log('  Operation [%s], with [%s] parameters was read.', $this->name, count($this->umlParameters));
    }

}
