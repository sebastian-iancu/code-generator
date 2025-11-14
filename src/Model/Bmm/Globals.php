<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use OpenEHR\Tools\CodeGen\Model\Collection;

final class Globals
{

    /** @var Collection<string, BmmSchema> */
    protected static Collection $schemas;

    private static function getSchemas(): Collection
    {
        return self::$schemas ??= new Collection();
    }

    public static function addSchema(BmmSchema $schema): void
    {
        self::getSchemas()->add($schema);
    }

    public static function getSchema(string $key): ?BmmSchema
    {
        $schema = self::getSchemas()->get($key);
        return $schema instanceof BmmSchema ? $schema : null;
    }

    public static function getClassPackageQName(string $className): ?string
    {
        /** @var BmmSchema $schema */
        foreach (self::getSchemas() as $schema) {
            $qname = $schema->getClassPackageQName($className);
            if (!empty($qname)) {
                return $qname;
            }
        }
        return null;
    }
}
