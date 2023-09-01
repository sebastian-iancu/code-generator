<?php

namespace OpenEHR\Tools\CodeGen\Model;

use OpenEHR\Tools\CodeGen\Helper\Collection;
use SimpleXMLElement;

class UMLProperty extends UMLParameter
{

    public readonly string $description;
    public readonly bool $isStatic;
    public readonly bool $isReadOnly;
    public readonly mixed $default;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        parent::__construct($xmlNode);
        $this->description = (string)$xmlNode->ownedComment['body'];
        $this->isStatic = strcasecmp((string)$xmlNode['isStatic'], 'true') === 0;
        $this->isReadOnly = strcasecmp((string)$xmlNode['isReadOnly'], 'true') === 0;
        // detect default value
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

        self::log('  Property [%s] of type [%s] was read.', $this->name, $this->type->name);
    }

}
