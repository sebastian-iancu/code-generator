<?php

namespace OpenEHR\Tools\CodeGen\Model;

use SimpleXMLElement;

class TypeReference extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly string $referenceMethod;
    public readonly ?string $referentPath;
    public readonly ?string $referentType;

    public function __construct(SimpleXMLElement $xmlNode = null, string $key = '')
    {
        if (!$xmlNode) {
            $this->id = $key ?: 'Any';
            $this->name = $key ?: 'Any';
            $this->referenceMethod = 'implicit';
            $this->referentType = null;
            $this->referentPath = null;
        } elseif (isset($xmlNode['href'])) {
            $this->id = (string)$xmlNode['href'];
            $this->name = $this->extractTypeName($xmlNode);
            $this->referenceMethod = 'href';
            if (($nodes = $xmlNode->xpath('descendant::referenceExtension'))) {
                $this->referentType = (string)$nodes[0]['referentType'];
                $this->referentPath = (string)$nodes[0]['referentPath'];
            } else {
                $this->referentType = null;
                $this->referentPath = null;
            }
        } elseif (($idref = (string)$xmlNode->attributes('xmi', true)?->idref)) {
            $this->id = $idref;
            $this->name = $this->getNameById($xmlNode, $idref);
            $this->referenceMethod = 'idref';
            $this->referentType = null;
            $this->referentPath = null;
        } elseif ($xmlNode->xpath('descendant::qualifier')) {
            $id = $key ?: (string)$xmlNode->attributes('xmi', true)?->id;
            $qualifierType = new self($xmlNode->qualifier->type);
            if ((string)$xmlNode['aggregation'] === 'composite') {
                $this->name = 'Hash<' . $qualifierType->name . ', ' . $this->getNameById($xmlNode, $id) . '>';
                $this->referenceMethod = 'qualifier-composite';
            } else {
                $this->name = $qualifierType->name;
                $this->referenceMethod = 'qualifier';
            }
            $this->id = $this->getIdByName($xmlNode, $this->name);
            $this->referentType = null;
            $this->referentPath = null;
        } else {
            $this->id = $key ?: (string)$xmlNode->attributes('xmi', true)?->id;
            $this->name = $this->getNameById($xmlNode, $this->id);
            $this->referenceMethod = 'lookup';
            $this->referentType = null;
            $this->referentPath = null;
        }

        self::log('  TypeReference [%s] as [%s] was read.', $this->id, $this->name);
    }

    protected function extractTypeName(SimpleXMLElement $node): string
    {
        $nodes = $node->xpath('descendant::referenceExtension');
        if (!$nodes) {
            self::log("WARNING: referenceExtension node not found.");
            return '';
        }
        $parts = explode('::', (string)$nodes[0]['referentPath']);
        $type = end($parts);
        return match ($type) {
            'boolean' => 'Boolean',
            'char', 'byte' => 'Character',
            'double' => 'Double',
            'K' => 'String',
            'V', 'T' => 'Any',
            default => (string)$type,
        };
    }

    protected function getNameById(SimpleXMLElement $node, string $id = '', string $type = '', string $otherPredicate = ''): string
    {
        $predicates = ['(@name or ownedParameteredElement)'];
        if (preg_match('/^\w+$/', $id)) {
            $predicates[] = "@xmi:id='$id'";
        }
        if (preg_match('/^\w+$/', $type)) {
            $predicates[] = "@xmi:type='uml:$type'";
        }
        if (preg_match('/^[^\[\]\"]+$/', $otherPredicate)) {
            $predicates[] = $otherPredicate;
        }
        if (count($predicates) === 1) {
            self::log("WARNING: While resolving Name by Id, insufficient predicates [id:$id, type:$type, others:$otherPredicate] to query xml.");
            return '';
        }
        $predicates = implode(' and ', $predicates);
        $nodes = $node->xpath("//node()[$predicates]");
        if (!$nodes) {
            self::log("WARNING: While resolving Name by Id, xml-node not found for [$predicates].");
        } elseif (isset($nodes[0]['name'])) {
            return (string)$nodes[0]['name'];
        } elseif (isset($nodes[0]->ownedParameteredElement['name'])) {
            return (string)$nodes[0]->ownedParameteredElement['name'];
        }

        return '';
    }

    protected function getIdByName(SimpleXMLElement $node, string $name, string $type = '', string $otherPredicate = ''): string
    {
        $predicates = ['(@xmi:id)'];
        if (preg_match('/^[^\'"]+$/', $name)) {
            $predicates[] = "@name='$name'";
        }
        if (preg_match('/^\w+$/', $type)) {
            $predicates[] = "@xmi:type='uml:$type'";
        }
        if (preg_match('/^[^\[\]\"]+$/', $otherPredicate)) {
            $predicates[] = $otherPredicate;
        }
        if (count($predicates) === 1) {
            self::log("WARNING: While resolving Id by Name, insufficient predicates [Name:$name, type:$type, others:$otherPredicate] to query xml.");
            return '';
        }
        $predicates = implode(' and ', $predicates);
        $nodes = $node->xpath("//node()[$predicates]");
        if (!$nodes) {
            self::log("WARNING: While resolving Id by Name, xml-node not found for [$predicates].");
        } else {
            return (string)$nodes[0]->attributes('xmi', true)?->id;
        }

        return '';
    }
}
