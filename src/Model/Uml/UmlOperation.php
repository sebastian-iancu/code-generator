<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use OpenEHR\Tools\CodeGen\Model\Collection;
use SimpleXMLElement;

class UmlOperation implements CollectableInterface
{

    use CollectableTrait;
    use ConsoleTrait;

    public readonly string $id;
    public readonly string $name;
    public readonly string $description;
    public readonly bool $isAbstract;
    public readonly Collection $umlParameters;
    public readonly Collection $umlConstraints;
    public readonly UmlTypeReference $return;
    public readonly ?int $minOccurs;
    public readonly ?int $maxOccurs;
    public readonly ?array $aliases;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];
        $this->description = (string)$xmlNode->ownedComment['body'];
        $this->isAbstract = (string)$xmlNode['isAbstract'] === 'true';
        // collect parameters
        $this->umlParameters = new Collection();
        foreach ($xmlNode->xpath("ownedParameter[@xmi:type='uml:Parameter' and (not(@direction) or not(@direction='return'))]") as $umlParameterNode) {
            $item = new UmlParameter($umlParameterNode);
            $this->umlParameters->add($item);
        }
        // collect Pre- and Post-constraints
        $this->umlConstraints = new Collection();
        $nodes = $xmlNode->xpath("ownedRule[@xmi:type='uml:Constraint' and specification/body]") ?: [];
        foreach ($nodes as $umlConstraintNode) {
            $item = new UmlConstraint($umlConstraintNode);
            $this->umlConstraints->add($item);
        }
        // detect return type
        $nodes = $xmlNode->xpath("ownedParameter[@xmi:type='uml:Parameter' and @direction='return']");
        if (count($nodes) === 1) {
            $returnParameter = new UmlParameter($nodes[0]);
            $this->return = $returnParameter->type;
            $this->minOccurs = $returnParameter->minOccurs;
            $this->maxOccurs = $returnParameter->maxOccurs;
        } else {
            $this->return = new UmlTypeReference(null, 'void');
            $this->minOccurs = null;
            $this->maxOccurs = null;
        }
        // detect aliases, these are usually under the openEHR_UML_profile namespace
        $aliases = [];
        if (array_key_exists('openEHR_UML_profile', $xmlNode->getDocNamespaces() ?: [])) {
            $nodes = array_merge(
                $xmlNode->xpath("//openEHR_UML_profile:Operator[@base_Operation='{$this->id}' and ops]") ?: [],
                $xmlNode->xpath("//openEHR_UML_profile:Symbolic_operator[@base_Operation='{$this->id}' and sym_ops]") ?: [],
            );
            foreach ($nodes as $node) {
                $ops = implode(', ', array_filter([(string)$node->ops, (string)$node->sym_ops]));
                $ops = str_replace(['|', '*'], ['&#124;', '&#42;'], $ops);
                if (preg_match_all('/\"([^"]+)\"(?:,\s*)?/', $ops, $m)) {
                    $aliases = array_merge($aliases, $m[1]);
                }
            }
        }
        $this->aliases = $aliases;

        self::log('  Operation [%s], with [%s] parameters was read.', $this->name, count($this->umlParameters));
    }

}
