<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Model\Collection;
use SimpleXMLElement;

class UmlClass extends AbstractUmlClass
{

    public readonly bool $isAbstract;
    public readonly Collection $umlGeneralizations;
    public readonly Collection $umlTemplateParameters;
    public readonly ?UmlTemplateBinding $templateBinding;
    public readonly Collection $umlProperties;
    public readonly Collection $umlOperations;
    public readonly Collection $umlConstraints;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        parent::__construct($xmlNode);
        $this->isAbstract = (string)$xmlNode['isAbstract'] === 'true';
        // collect generalizations
        $this->umlGeneralizations = new Collection();
        $nodes = $xmlNode->xpath("generalization[@xmi:type='uml:Generalization']") ?: [];
        foreach ($nodes as $umlGeneralizationNode) {
            $item = new UmlGeneralization($umlGeneralizationNode);
            $this->umlGeneralizations->add($item);
        }
        // check for templateParameters
        $this->umlTemplateParameters = new Collection();
        $nodes = $xmlNode->xpath("ownedTemplateSignature[@xmi:type='uml:RedefinableTemplateSignature']/ownedParameter") ?: [];
        foreach ($nodes as $umlTemplateParameterNode) {
            $item = new UmlTemplateParameter($umlTemplateParameterNode);
            $this->umlTemplateParameters->add($item);
            $this->umlTemplateParameters->add($item, $item->parameteredElement);
        }
        // check for templateBinding
        $nodes = $xmlNode->xpath("templateBinding[@xmi:type='uml:TemplateBinding']") ?: [];
        if (count($nodes) === 1) {
            $this->templateBinding = new UmlTemplateBinding($nodes[0]);
        } else {
            $this->templateBinding = null;
        }
        // collect properties
        $this->umlProperties = new Collection();
        $nodes = $xmlNode->xpath("ownedAttribute[@xmi:type='uml:Property']") ?: [];
        foreach ($nodes as $umlPropertyNode) {
            $item = new UmlProperty($umlPropertyNode);
            $this->umlProperties->add($item);
        }
        // collect functions
        $this->umlOperations = new Collection();
        $nodes = $xmlNode->xpath("ownedOperation[@xmi:type='uml:Operation']") ?: [];
        foreach ($nodes as $umlOperationNode) {
            $item = new UmlOperation($umlOperationNode);
            $this->umlOperations->add($item);
        }
        // collect constraints
        $this->umlConstraints = new Collection();
        $nodes = $xmlNode->xpath("ownedRule[@xmi:type='uml:Constraint' and specification/body]") ?: [];
        foreach ($nodes as $umlConstraintNode) {
            $item = new UmlConstraint($umlConstraintNode);
            $this->umlConstraints->add($item);
        }

        self::log('  Class [%s] was read.', $this->name);
    }

    public function isGenericType(): bool
    {
        return $this->umlTemplateParameters->count() > 0;
    }

    public function getGenericParameterName(): string
    {
        return (string)$this->umlTemplateParameters->getIterator()->key();
    }
}
