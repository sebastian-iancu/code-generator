<?php

namespace OpenEHR\Tools\CodeGen\Model;

use OpenEHR\Tools\CodeGen\Helper\Collection;
use SimpleXMLElement;

class UMLProperty extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly string $description;
    public readonly bool $isStatic;
    public readonly bool $isReadOnly;
    public readonly TypeReference $type;
    public readonly ?string $templateParameterId;
    public readonly int $minOccurs;
    public readonly int $maxOccurs;
    public readonly mixed $default;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];
        $this->description = (string)$xmlNode->ownedComment['body'];
        $this->isStatic = strcasecmp((string)$xmlNode['isStatic'], 'true') === 0;
        $this->isReadOnly = strcasecmp((string)$xmlNode['isReadOnly'], 'true') === 0;
        // get the desired type name
        if (isset($xmlNode['type'])) {
            $type = new TypeReference($xmlNode, (string)$xmlNode['type']);
        } elseif (isset($xmlNode->type)) {
            $type = new TypeReference($xmlNode->type);
        } else {
            $this->log("WARNING: Type undefined for $this->id. Using [Any]. " . $xmlNode->saveXML());
            $type = new TypeReference($xmlNode, 'Any');
        }
        // detect templateParameter
        $nodes = $xmlNode->xpath("ancestor::packagedElement[@xmi:type='uml:Class']/ownedTemplateSignature/ownedParameter[ownedParameteredElement/@xmi:id='{$type->id}']");
        if ($nodes) {
            $templateParameter = new UMLTemplateParameter($nodes[0]);
            $this->type = $templateParameter->type;
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
        if (isset($xmlNode->upperValue)) {
            $this->maxOccurs = (string)$xmlNode->upperValue['value'] === '*' ? -1 : (int)$xmlNode->upperValue['value'];
        } else {
            $this->maxOccurs = 1;
        }
        if (isset($xmlNode->defaultValue)) {
            $this->default = match ((string)$xmlNode->defaultValue->attributes('xmi', true)?->type) {
                'uml:LiteralInteger' => (int)(string)$xmlNode->defaultValue['value'],
                'uml:LiteralReal' => (float)(string)$xmlNode->defaultValue['value'],
                'uml:LiteralBoolean' => (bool)(string)$xmlNode->defaultValue['value'],
                default => str_replace(array('&#39;', '&quote;'), '', (string)$xmlNode->defaultValue['value']),
            };
        } else {
            $this->default = null;
        }
        $this->log('  Property [%s], type [%s] was read.', $this->name, $this->type->name);
    }

}
