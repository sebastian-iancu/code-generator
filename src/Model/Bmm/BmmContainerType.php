<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use Symfony\Component\Yaml\Tag\TaggedValue;


readonly class BmmContainerType implements JsonSerializable, CollectableInterface
{

    private string $name;

    use CollectableTrait;

    /**
     * @param string $containerType
     * @param string|null $type
     * @param BmmContainerType|BmmGenericType|BmmSimpleType|null $typeDef
     */
    public function __construct(
        public string $containerType,
        public ?string $type = null,
        public BmmContainerType|BmmGenericType|BmmSimpleType|null $typeDef = null,
    )
    {
        $this->name = '';
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
     * @return TaggedValue
     */
    public function yamlSerialize(): TaggedValue
    {
        return new TaggedValue('P_BMM_CONTAINER_TYPE', array_filter([
            'container_type' => $this->containerType,
            'type' => $this->type,
            'type_def' => $this->typeDef?->yamlSerialize(),
        ]));
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
