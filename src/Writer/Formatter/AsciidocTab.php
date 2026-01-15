<?php

namespace OpenEHR\Tools\CodeGen\Writer\Formatter;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmEnumerationInteger;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmEnumerationString;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmInterface;

class AsciidocTab
{

    public function format(AbstractBmmClass $class, string $classFilename): string
    {
        $className = $class->getName();
        $classType = match(get_class($class)) {
            BmmInterface::class => 'Interface',
            BmmEnumerationString::class, BmmEnumerationInteger::class => 'Enumeration',
            default => 'Class',
        };
        $location = 'ROOT:partial$'; // '../' as legacy

        return <<<ASCIIDOC
=== {$className} $classType

[tabs]
====
Definition::
+
include::{$location}definitions/{$classFilename}[]

BMM::
+
include::{$location}BMMs/{$classFilename}[]

UML::
+
include::{$location}plantUML/classes/{$classFilename}[]

====
ASCIIDOC;
    }

}
