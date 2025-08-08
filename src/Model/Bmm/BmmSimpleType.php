<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\YamlSerializable;


readonly class BmmSimpleType extends AbstractBmmType implements JsonSerializable, YamlSerializable
{

    /**
     * @param string $type
     */
    public function __construct(
        public string $type,
    )
    {
        parent::__construct();;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function yamlSerialize(): array
    {
        return [
            'type' => $this->type,
        ];
    }

    /**
     * Create a BMMSimpleType from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
        );
    }
}
