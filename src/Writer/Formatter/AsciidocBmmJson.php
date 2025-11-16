<?php

namespace OpenEHR\Tools\CodeGen\Writer\Formatter;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;

class AsciidocBmmJson
{
    public function format(AbstractBmmClass $class): string
    {
        $data = $class->jsonSerialize();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return <<<ASCIIDOC
[source,json]
--------
{$json}
--------
ASCIIDOC;
    }
}
