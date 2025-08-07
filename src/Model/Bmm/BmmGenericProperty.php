<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use Symfony\Component\Yaml\Tag\TaggedValue;

/**
 * Class representing a BMM generic property
 */
readonly class BmmGenericProperty implements JsonSerializable, CollectableInterface
{

    use CollectableTrait;

    /**
     * @param string $name
     * @param BmmGenericType $typeDef
     * @param string|null $documentation
     * @param bool|null $isMandatory
     */
    public function __construct(
        public string $name,
        public BmmGenericType $typeDef,
        public ?string $documentation = null,
        public ?bool $isMandatory = false,
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
            '_type' => 'P_BMM_GENERIC_PROPERTY',
            'name' => $this->name,
            'documentation' => $this->documentation,
            'is_mandatory' => $this->isMandatory,
            'type_def' => $typeDef,
        ]);
    }

    /**
     * @return TaggedValue
     */
    public function yamlSerialize(): TaggedValue
    {
        return new TaggedValue('P_BMM_GENERIC_PROPERTY', array_filter([
            'name' => $this->name,
            'documentation' => $this->documentation,
            'is_mandatory' => $this->isMandatory,
            'type_def' => $this->typeDef->yamlSerialize()->getValue(),
        ]));
    }

    /**
     * Create a BMMGenericProperty from a JSON array
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
            isMandatory: $data['is_mandatory'] ?? false,
        );
    }
}
