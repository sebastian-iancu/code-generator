<?php

namespace OpenEHR\Tools\CodeGen\Writer\Formatter;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;

class AsciidocTab
{

    public function format(AbstractBmmClass $class, string $classFilename): string
    {
        $className = $class->getName();
        $location = 'ROOT:partial$'; // '../' as legacy

        return <<<ASCIIDOC
=== {$className} Class

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
