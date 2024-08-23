<?php

namespace OpenEHR\Tools\CodeGen\Model;

use Generator;
use RuntimeException;
use SimpleXMLElement;

class UMLFile extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    public readonly UMLPackage $umlPackage;
    protected readonly SimpleXMLElement $xmi;

//    public readonly Collection $allUmlPackages;

    public function __construct(string $fileName)
    {
        self::log('Reading [%s] filename...', $fileName);
        $contents = file_get_contents($fileName);
        if (!$contents) {
            throw new RuntimeException("File missing or not readable: $fileName.");
        }
        libxml_clear_errors();
        $xml = simplexml_load_string($contents);
        $error = libxml_get_last_error();
        if ($error) {
            throw new RuntimeException("libxml errors in $fileName: $error->message at line $error->line.");
        }

        $this->id = basename($fileName);
        $this->xmi = $xml;

        $mainPackageNode = $this->getMainPackageNode();
        $this->umlPackage = new UMLPackage($mainPackageNode);
        $this->name = $this->umlPackage->name;

        self::log('  File [%s] was read.', $this->name);
    }

    protected function getMainPackageNode(): SimpleXMLElement
    {
        $nodes = $this->xmi->xpath('//uml:Package[@xmi:type="uml:Package"]');
        if (!$nodes) {
            throw new RuntimeException("XMI errors in $this->id: main package not found.");
        }
        if (count($nodes) > 1) {
            self::log("WARNING: Found more then one UML Package in the $this->id file. This will only process the first one");
        }
        return $nodes[0];
    }

    public function getRelease(): string
    {
        return str_replace([
            $this->name . '-v',
            '.xmi'
        ], '', $this->id);
    }

//    protected function recursiveCollectUmlPackages(UMLPackage $umlPackage, string $prefix): void
//    {
//        foreach ($umlPackage->umlPackages as $childUmlPackage) {
//            $subPrefix = $prefix . '::' . $childUmlPackage->name;
//            $this->allUmlPackages->add($childUmlPackage, $subPrefix);
//            $this->recursiveCollectUmlPackages($childUmlPackage, $subPrefix);
//        }
//    }


    public function getPackages(string $prefix): Generator
    {
        self::log('Searching for [%s] in [%s](%s)...', $prefix, $this->id, $this->name);
        $parts = explode('::', $prefix);
        $packageId = array_shift($parts);
        if ($this->umlPackage->id === $packageId || $this->umlPackage->name === $packageId) {
            self::log('Found [%s](%s) umlPackage.', $this->umlPackage->id, $this->umlPackage->name);
            if (!$parts) {
                yield $this->umlPackage;
            } else {
                foreach ($this->umlPackage->getPackages(implode('::', $parts)) as $umlPackage) {
                    yield $umlPackage;
                }
            }
        }
    }

}
