<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;

/**
 * Class representing a BMM container function parameter
 */
readonly class BmmContainerFunctionParameter extends AbstractBmmFunctionParameter implements JsonSerializable
{

    /**
     * @param string $name
     * @param BmmContainerType $typeDef
     * @param string|null $documentation
     * @param bool|null $isNullable
     * @param Interval|null $cardinality
     */
    public function __construct(
        public string $name,
        public BmmContainerType $typeDef,
        public ?string $documentation = null,
        public ?bool $isNullable = false,
        public ?Interval $cardinality = new Interval(),
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
            '_type' => 'P_BMM_CONTAINER_FUNCTION_PARAMETER',
            'name' => $this->name,
            'is_nullable' => $this->isNullable,
            'documentation' => $this->documentation,
            'type_def' => $typeDef,
            'cardinality' => $this->cardinality,
        ]);
    }

    /**
     * Create a BMMContainerFunctionParameter from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            typeDef: BmmContainerType::fromArray($data['type_def']),
            documentation: $data['documentation'] ?? null,
            isNullable: $data['is_nullable'] ?? false,
            cardinality: isset($data['cardinality']) ? Interval::fromArray($data['cardinality']) : new Interval(),
        );
    }
}
