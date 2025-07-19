<?php

namespace OpenEHR\Tools\CodeGen\Model;

interface CollectableInterface
{


    public function getName(): string;

    public function getAlias(): ?string;
}
