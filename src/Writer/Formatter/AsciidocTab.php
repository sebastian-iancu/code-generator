<?php

namespace OpenEHR\Tools\CodeGen\Writer\Formatter;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;

class AsciidocTab
{

    public function format(AbstractBmmClass $class, string $classFilename): string
    {
        $className = $class->getName();

        return <<<ASCIIDOC
=== {$className} Class

[tabs]
====
Definition::
+
include::../definitions/{$classFilename}[]

BMM::
+
include::../BMMs/{$classFilename}[]

UML::
+
include::../plantUML/classes/{$classFilename}[]

====
ASCIIDOC;
    }

}
