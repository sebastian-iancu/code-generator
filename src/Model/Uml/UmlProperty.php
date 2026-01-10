<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use SimpleXMLElement;

class UmlProperty extends UmlParameter
{

    public readonly bool $isStatic;
    public readonly bool $isReadOnly;
    public readonly mixed $default;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        parent::__construct($xmlNode);
        $this->isStatic = strcasecmp((string)$xmlNode['isStatic'], 'true') === 0;
        $this->isReadOnly = strcasecmp((string)$xmlNode['isReadOnly'], 'true') === 0;
        // detect default value
        if (isset($xmlNode->defaultValue)) {
            $defaultValue = (string)($xmlNode->defaultValue['value'] ?? $xmlNode->defaultValue->body ?? '');
            $this->default = match ((string)$xmlNode->defaultValue->attributes('xmi', true)?->type) {
                'uml:LiteralInteger' => (int)$defaultValue,
                'uml:LiteralReal' => (float)$defaultValue,
                'uml:LiteralBoolean' => (bool)$defaultValue,
                default => str_replace(array('&#39;', '&quote;'), '', $defaultValue),
            };
        } else {
            $this->default = null;
        }

        self::log('  Property [%s] of type [%s] was read.', $this->name, $this->type->name);
    }

}
