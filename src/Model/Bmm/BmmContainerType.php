<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;


readonly class BmmContainerType extends AbstractBmmType implements JsonSerializable
{

    /**
     * @param string $containerType
     * @param string|null $type
     * @param AbstractBmmType|null $typeDef
     */
    public function __construct(
        public string $containerType,
        public ?string $type = null,
        public ?AbstractBmmType $typeDef = null,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            '_type' => 'P_BMM_CONTAINER_TYPE',
            'container_type' => $this->containerType,
            'type' => $this->type,
            'type_def' => $this->typeDef,
        ]);
    }

    /**
     * Create a BMMContainerType from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            containerType: $data['container_type'],
            type: $data['type'] ?? null,
            typeDef: isset($data['type_def']) ? AbstractBmmType::fromArray($data['type_def']) : null,
        );

    }
}
