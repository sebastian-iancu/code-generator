<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Model\AbstractItem;
use SimpleXMLElement;

class UmlTemplateParameter extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly string $parameteredElement;
    public readonly UmlTypeReference $type;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->parameteredElement = (string)$xmlNode['parameteredElement'];
        $ref = new UmlTypeReference($xmlNode, $this->parameteredElement);
        $this->name = $ref->name;
        if ($xmlNode->constrainingClassifier) {
            $this->type = new UmlTypeReference($xmlNode->constrainingClassifier);
        } else {
            $this->type = new UmlTypeReference();
        }

        self::log('  TemplateParameter [%s] as [%s](%s) was read.', $this->id, $this->name, $this->type->name);
    }

}
