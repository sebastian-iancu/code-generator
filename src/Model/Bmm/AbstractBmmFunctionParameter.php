<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use OpenEHR\Tools\CodeGen\Model\CollectableInterface;

/**
 * Class representing an abstract BMM property
 */
abstract readonly class AbstractBmmFunctionParameter implements CollectableInterface
{

    use CollectableTrait;

    public string $name;

    public static function fromArray(array $data): BmmContainerFunctionParameter|BmmGenericFunctionParameter|BmmSingleFunctionParameter|BmmSingleFunctionParameterOpen
    {
        $type = $data['_type'] ?? 'P_BMM_SINGLE_FUNCTION_PARAMETER';
        return match ($type) {
            'P_BMM_SINGLE_FUNCTION_PARAMETER_OPEN' => BmmSingleFunctionParameterOpen::fromArray($data),
            'P_BMM_CONTAINER_FUNCTION_PARAMETER' => BmmContainerFunctionParameter::fromArray($data),
            'P_BMM_GENERIC_FUNCTION_PARAMETER' => BmmGenericFunctionParameter::fromArray($data),
            default => BmmSingleFunctionParameter::fromArray($data),
        };
    }

}
