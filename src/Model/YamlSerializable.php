<?php

namespace OpenEHR\Tools\CodeGen\Model;

use Symfony\Component\Yaml\Tag\TaggedValue;

interface YamlSerializable
{

    /**
     * @return array<string, mixed>|array<mixed>|TaggedValue
     */
    public function yamlSerialize(): array|TaggedValue;
}
