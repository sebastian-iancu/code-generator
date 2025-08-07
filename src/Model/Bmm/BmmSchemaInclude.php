<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use OpenEHR\Tools\CodeGen\Model\YamlSerializable;

/**
 * Class representing the top-level BMM schema structure
 */
readonly class BmmSchemaInclude implements JsonSerializable, YamlSerializable, CollectableInterface
{

    /**
     * @param string $id
     */
    public function __construct(
        public string $id,
    )
    {
    }


    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'id' => $this->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function yamlSerialize(): array
    {
        return array_filter([
            'id' => $this->id,
        ]);
    }

    /**
     * Create a BMMSchemaInclude from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            id: $data['id'],
        );

        return $instance;
    }

    public function getName(): string
    {
        return $this->id;
    }

    public function getAlias(): ?string
    {
        return null;
    }
}
