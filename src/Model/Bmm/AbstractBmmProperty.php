<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

/**
 * Class representing an abstract BMM property
 */
abstract readonly class AbstractBmmProperty
{

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): BmmContainerProperty|BmmGenericProperty|BmmSingleProperty|BmmSinglePropertyOpen
    {
        $type = $data['_type'] ?? 'P_BMM_SINGLE_PROPERTY';
        return match ($type) {
            'P_BMM_SINGLE_PROPERTY_OPEN' => BmmSinglePropertyOpen::fromArray($data),
            'P_BMM_CONTAINER_PROPERTY' => BmmContainerProperty::fromArray($data),
            'P_BMM_GENERIC_PROPERTY' => BmmGenericProperty::fromArray($data),
            default => BmmSingleProperty::fromArray($data),
        };
    }

}
