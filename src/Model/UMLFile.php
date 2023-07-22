<?php

namespace OpenEHR\Tools\CodeGen\Model;

use RuntimeException;
use SimpleXMLElement;

class UMLFile extends AbstractItem
{

    public readonly string $id;
    public readonly string $name;
    protected readonly SimpleXMLElement $xmi;

    public readonly UMLPackage $umlPackage;
//    public readonly Collection $allUmlPackages;

    public function __construct(string $fileName)
    {
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
//        $this->allUmlPackages = new Collection();
//        $this->recursiveCollectUmlPackages($this->umlPackage, $this->name);

        $this->log('  File [%s] was read.', $this->name);
    }

    protected function getMainPackageNode(): SimpleXMLElement
    {
        $nodes = $this->xmi->xpath('//uml:Package[@xmi:type="uml:Package"]');
        if (!$nodes) {
            throw new RuntimeException("XMI errors in $this->id: main package not found.");
        }
        if (count($nodes) > 1) {
            $this->log("WARNING: Found more then one UML Package in the $this->id file. This will only process the first one");
        }
        return $nodes[0];
    }

//    protected function recursiveCollectUmlPackages(UMLPackage $umlPackage, string $prefix): void
//    {
//        foreach ($umlPackage->umlPackages as $childUmlPackage) {
//            $subPrefix = $prefix . '::' . $childUmlPackage->name;
//            $this->allUmlPackages->add($childUmlPackage, $subPrefix);
//            $this->recursiveCollectUmlPackages($childUmlPackage, $subPrefix);
//        }
//    }


}
