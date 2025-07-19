<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

trait CollectableTrait
{

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return $this->id;
    }

}
