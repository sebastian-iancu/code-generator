<?php

namespace OpenEHR\Tools\CodeGen\Model;

use OpenEHR\Tools\CodeGen\Helper\Collection;
use SimpleXMLElement;

class UMLPackage extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly string $description;
    public readonly Collection $umlPackages;
    public readonly Collection $umlClasses;

    public function __construct(SimpleXMLElement $xmlNode)
    {
        $this->id = (string)$xmlNode->attributes('xmi', true)?->id;
        $this->name = (string)$xmlNode['name'];
        $this->description = (string)$xmlNode->ownedComment['body'];
        // collecting all subPackages
        $this->umlPackages = new Collection();
        $predicates = implode(' and ', [
            '@xmi:type="uml:Package"',
            'packagedElement',
            'not(contains(@name, " diagrams"))',
            'not(@name="entity")',
            'not(@name="example")',
        ]);
        $nodes = $xmlNode->xpath("packagedElement[$predicates]") ?: [];
        foreach ($nodes as $umlPackageNode) {
            $umlPackage = new UMLPackage($umlPackageNode);
            $this->umlPackages->add($umlPackage);
        }
        // collecting all classes
        $this->umlClasses = new Collection();
        $predicates = implode(' and ', [
            '@xmi:type="uml:Class"',
            'not(@templateParameter)',
            'not(contains(@name, "TUPLE1<"))',
            'not(contains(@name, "TUPLE2<"))',
            'not(contains(@name, "FUNCTION<"))',
        ]);
        $nodes = $xmlNode->xpath("packagedElement[$predicates]") ?: [];
        foreach ($nodes as $umlClassNode) {
            $item = new UMLClass($umlClassNode);
            $this->umlClasses->add($item);
        }
        $nodes = $xmlNode->xpath('packagedElement[@xmi:type="uml:Interface"]') ?: [];
        foreach ($nodes as $umlClassNode) {
            $item = new UMLInterface($umlClassNode);
            $this->umlClasses->add($item);
        }
        $nodes = $xmlNode->xpath('packagedElement[@xmi:type="uml:Enumeration"]') ?: [];
        foreach ($nodes as $umlClassNode) {
            $item = new UMLEnumeration($umlClassNode);
            $this->umlClasses->add($item);
        }
        self::log('  Package [%s] containing %s subpackages and %s classes was read.', $this->name, $this->umlPackages->count(), $this->umlClasses->count());
    }


    public function getPackages(string $prefix): \Generator
    {
        /** @var UMLPackage|null $umlPackage */
        self::log('Searching for [%s] in [%s](%s)...', $prefix, $this->id, $this->name);
        if ($prefix === '*' || $prefix === '') {
            foreach ($this->umlPackages as $umlPackage) {
                yield $umlPackage;
            }
        } else {
            $parts = explode('::', $prefix);
            $packageId = array_shift($parts);
            $umlPackage = $this->umlPackages->get($packageId);
            if ($umlPackage) {
                self::log('Found [%s](%s) umlPackage.', $umlPackage->id, $this->name);
                if (!$parts) {
                    yield $umlPackage;
                } else {
                    foreach ($umlPackage->getPackages(implode('::', $parts)) as $umlPackage) {
                        yield $umlPackage;
                    }
                }
            }

        }
    }

}
