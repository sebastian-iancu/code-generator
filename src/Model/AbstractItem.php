<?php

namespace OpenEHR\Tools\CodeGen\Model;

use OpenEHR\Tools\CodeGen\Helper\ConsoleTrait;

abstract class AbstractItem
{

    use ConsoleTrait;

    public readonly string $id;
    public readonly string $name;

}
