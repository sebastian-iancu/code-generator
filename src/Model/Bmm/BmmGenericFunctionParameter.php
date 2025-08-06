<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;

/**
 * Class representing a BMM generic function parameter
 */
readonly class BmmGenericFunctionParameter implements JsonSerializable, CollectableInterface
{

    use CollectableTrait;

    /**
     * @param string $name
     * @param BmmGenericType $typeDef
     * @param string|null $documentation
     * @param bool|null $isNullable
     */
    public function __construct(
        public string $name,
        public BmmGenericType $typeDef,
        public ?string $documentation = null,
        public ?bool $isNullable = false,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $typeDef = $this->typeDef->jsonSerialize();
        unset($typeDef['_type']);
        return array_filter([
            '_type' => 'P_BMM_GENERIC_FUNCTION_PARAMETER',
            'name' => $this->name,
            'documentation' => $this->documentation,
            'is_nullable' => $this->isNullable,
            'type_def' => $typeDef,
        ]);
    }

    /**
     * Create a BMMGenericFunctionParameter from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            typeDef: BmmGenericType::fromArray($data['type_def']),
            documentation: $data['documentation'] ?? null,
            isNullable: $data['is_nullable'] ?? false,
        );
    }
}
