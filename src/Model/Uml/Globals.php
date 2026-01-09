<?php

namespace OpenEHR\Tools\CodeGen\Model\Uml;

use OpenEHR\Tools\CodeGen\Model\Collection;

final class Globals
{

    /** @var Collection<string, AbstractUmlClass> */
    protected static Collection $classes;

    private static function getUmlClasses(): Collection
    {
        return self::$classes ??= new Collection();
    }

    public static function addUmlClass(AbstractUmlClass $umlClass): void
    {
        self::getUmlClasses()->add($umlClass);
    }

    public static function getUmlClass(string $key): ?AbstractUmlClass
    {
        $umlClass = self::getUmlClasses()->get($key);
        return $umlClass instanceof AbstractUmlClass ? $umlClass : null;
    }

    public static function flush(): void
    {
        self::getUmlClasses()->flush();
    }

}
