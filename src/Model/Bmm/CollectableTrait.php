<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

trait CollectableTrait
{

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return null;
    }

}
