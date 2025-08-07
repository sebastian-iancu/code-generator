<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use OpenEHR\Tools\CodeGen\Model\YamlSerializable;

/**
 * Class representing a BMM single property
 */
readonly class BmmConstant implements JsonSerializable, YamlSerializable, CollectableInterface
{

    use CollectableTrait;

    /**
     * @param string $name
     * @param string $type
     * @param string|null $documentation
     * @param mixed|null $value
     */
    public function __construct(
        public string $name,
        public string $type,
        public ?string $documentation = null,
        public mixed $value = null,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'documentation' => $this->documentation,
            'type' => $this->type,
            'value' => $this->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function yamlSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'documentation' => $this->documentation,
            'type' => $this->type,
            'value' => $this->value,
        ]);
    }

    /**
     * Create a BMMConstant from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            documentation: $data['documentation'] ?? null,
            value: $data['value'] ?? null,
        );
    }
}
