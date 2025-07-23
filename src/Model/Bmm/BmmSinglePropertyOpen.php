<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;

/**
 * Class representing a BMM single property
 */
readonly class BmmSinglePropertyOpen extends AbstractBmmProperty implements JsonSerializable
{

    /**
     * @param string $name
     * @param string $type
     * @param string|null $documentation
     * @param bool|null $isMandatory
     */
    public function __construct(
        public string $name,
        public string $type,
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
        return array_filter([
            '_type' => 'P_BMM_SINGLE_PROPERTY_OPEN',
            'name' => $this->name,
            'documentation' => $this->documentation,
            'is_mandatory' => $this->isMandatory,
            'type' => $this->type,
        ]);
    }

    /**
     * Create a BMMSingleProperty from a JSON array
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
            isMandatory: $data['is_mandatory'] ?? false,
        );
    }
}
