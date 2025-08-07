<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;

/**
 * Class representing a BMM generic parameter
 */
readonly class BmmGenericParameter implements JsonSerializable, CollectableInterface
{

    use CollectableTrait;

    /**
     * @param string $name
     * @param string|null $conformsToType
     */
    public function __construct(
        public string $name,
        public ?string $conformsToType = null,
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
            'conforms_to_type' => $this->conformsToType,
        ]);
    }
    
    /**
     * @return array<string, mixed>
     */
    public function yamlSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'conforms_to_type' => $this->conformsToType,
        ]);
    }

    /**
     * Create a BMMGenericParameter from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            name: $data['name'],
            conformsToType: $data['conforms_to_type'] ?? null,
        );

        return $instance;
    }
}
