<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use OpenEHR\Tools\CodeGen\Model\CollectableInterface;

/**
 * Class representing an abstract BMM class
 */
abstract readonly class AbstractBmmClass implements CollectableInterface
{

    use CollectableTrait;

    public string $name;

    public static function fromArray(array $data): BmmEnumerationString|BmmEnumerationInteger|BmmClass
    {
        $type = $data['_type'] ?? 'P_BMM_CLASS';
        return match ($type) {
            'P_BMM_ENUMERATION_STRING' => BmmEnumerationString::fromArray($data),
            'P_BMM_ENUMERATION_INTEGER' => BmmEnumerationInteger::fromArray($data),
            default => BmmClass::fromArray($data),
        };
    }

}
