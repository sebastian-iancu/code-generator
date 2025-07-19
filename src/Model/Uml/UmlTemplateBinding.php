<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use OpenEHR\Tools\CodeGen\Model\Collection;
use SimpleXMLElement;

class UmlTemplateBinding implements CollectableInterface
{

    use CollectableTrait;
    use ConsoleTrait;

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
                $item = new UmlTemplateParameterSubstitution($parameterSubstitutionNode);
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
