<?php

namespace OpenEHR\Tools\CodeGen\Model;

use SimpleXMLElement;

class UMLTemplateParameter extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly string $parameteredElement;
    public readonly TypeReference $type;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->parameteredElement = (string)$xmlNode['parameteredElement'];
        $ref = new TypeReference($xmlNode, $this->parameteredElement);
        $this->name = $ref->name;
        if ($xmlNode->constrainingClassifier) {
            $this->type = new TypeReference($xmlNode->constrainingClassifier);
        } else {
            $this->type = new TypeReference();
        }

        self::log('  TemplateParameter [%s] as [%s](%s) was read.', $this->id, $this->name, $this->type->name);
    }

}
