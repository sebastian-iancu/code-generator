<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use SimpleXMLElement;

class AbstractUmlAttribute implements CollectableInterface
{

    use CollectableTrait;
    use ConsoleTrait;

    public readonly string $id;
    public readonly string $name;
    public readonly UmlTypeReference $type;
    public readonly ?string $templateParameterId;
    public readonly int $minOccurs;
    public readonly int $maxOccurs;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];

        // detect type
        if (isset($xmlNode['type'])) {
            $type = new UmlTypeReference($xmlNode, (string)$xmlNode['type']);
        } elseif (isset($xmlNode->type)) {
            $type = new UmlTypeReference($xmlNode->type);
        } else {
            self::log("WARNING: Type undefined for $this->id. Using [Any]. " . $xmlNode->saveXML());
            $type = new UmlTypeReference($xmlNode, 'Any');
        }
        // detect if the type is a templateParameter
        $nodes = $xmlNode->xpath("ancestor::packagedElement[@xmi:type='uml:Class']/ownedTemplateSignature/ownedParameter[ownedParameteredElement/@xmi:id='{$type->id}']");
        if ($nodes) {
            $templateParameter = new UmlTemplateParameter($nodes[0]);
            $this->type = $type;
            $this->templateParameterId = $templateParameter->id;
        } else {
            $this->type = $type;
            $this->templateParameterId = null;
        }

        // process lower, upper occurrence and default value
        if (isset($xmlNode->lowerValue)) {
            $this->minOccurs = (string)$xmlNode->lowerValue['value'] === '*' ? 0 : (int)$xmlNode->lowerValue['value'];
        } else {
            $this->minOccurs = 1;
        }
        if ($this->type->referenceMethod === 'qualifier-composite') {
            $this->maxOccurs = 1;
        } elseif (isset($xmlNode->upperValue)) {
            $this->maxOccurs = (string)$xmlNode->upperValue['value'] === '*' ? -1 : (int)$xmlNode->upperValue['value'];
        } else {
            $this->maxOccurs = 1;
        }

    }

}
