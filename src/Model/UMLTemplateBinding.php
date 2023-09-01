<?php

namespace OpenEHR\Tools\CodeGen\Model;

use OpenEHR\Tools\CodeGen\Helper\Collection;
use SimpleXMLElement;

class UMLTemplateBinding extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly Collection $parameterSubstitutions;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $signature = (string)$xmlNode['signature'];
        if ($signature) {
            $nodes = $xmlNode->xpath("//ownedTemplateSignature[@xmi:id='$signature']/..");
            if ($nodes) {
                $this->name = (string)$nodes[0]['name'];
            } else {
                $this->name = "bindingSignature:$signature";
            }
            $this->parameterSubstitutions = new Collection();
            foreach ($xmlNode->parameterSubstitution as $parameterSubstitutionNode) {
                $item = new UMLTemplateParameterSubstitution($parameterSubstitutionNode);
                $this->parameterSubstitutions->add($item);
            }
        } else {
            self::log("WARNING: TemplateBinding without reference at $this->id.");
            $this->name = '';
            $this->parameterSubstitutions = new Collection();
        }

        self::log('  TemplateBinding [%s] was read.', $this->name);
    }

}
