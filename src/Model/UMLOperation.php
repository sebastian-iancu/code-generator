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
    public readonly Collection $umlConstraints;
    public readonly TypeReference $return;
    public readonly int $minOccurs;
    public readonly int $maxOccurs;

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
        // collect Pre- and Post-constraints
        $this->umlConstraints = new Collection();
        $nodes = $xmlNode->xpath("ownedRule[@xmi:type='uml:Constraint' and specification/body]") ?: [];
        foreach ($nodes as $umlConstraintNode) {
            $item = new UMLConstraint($umlConstraintNode);
            $this->umlConstraints->add($item);
        }
        // detect return type
        $nodes = $xmlNode->xpath("ownedParameter[@xmi:type='uml:Parameter' and @direction='return']");
        if (count($nodes) === 1) {
            $returnParameter = new UMLParameter($nodes[0]);
            $this->return = $returnParameter->type;
            $this->minOccurs = $returnParameter->minOccurs;
            $this->maxOccurs = $returnParameter->maxOccurs;
        } else {
            $this->return = new TypeReference(null, 'void');
        }

        self::log('  Operation [%s], with [%s] parameters was read.', $this->name, count($this->umlParameters));
    }

}
