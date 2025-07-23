<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

/**
 * Class representing an abstract BMM type
 */
abstract readonly class AbstractBmmType
{

    public static function fromArray(array $data): BmmContainerType|BmmGenericType|BmmSimpleType
    {
        $type = $data['_type'] ?? 'P_BMM_SIMPLE_TYPE';
        return match ($type) {
            'P_BMM_CONTAINER_TYPE' => BmmContainerType::fromArray($data),
            'P_BMM_GENERIC_TYPE' => BmmGenericType::fromArray($data),
            default => BmmSimpleType::fromArray($data),
        };
    }

}
