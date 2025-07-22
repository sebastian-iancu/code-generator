<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use SimpleXMLElement;

class UmlEnumeration implements CollectableInterface
{

    use CollectableTrait;
    use ConsoleTrait;

    public readonly string $id;
    public readonly string $name;
    public readonly string $description;
    /** @var array<string, array<string, mixed>> */
    public readonly array $enumerations;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];
        $this->description = (string)$xmlNode->ownedComment['body'];
        $enumerations = [];
        foreach ($xmlNode->ownedLiteral as $node) {
            $enumerations[] = [
                'name' => (string)$node['name'],
                'description' => (string)$node->ownedComment['body'],
            ];
        }
        $this->enumerations = $enumerations;

        self::log('  Enumerations [%s] with [%s] values was read.', $this->name, count($this->enumerations));
    }

}
