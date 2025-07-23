<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;


readonly class BmmSimpleType extends AbstractBmmType implements JsonSerializable
{

    /**
     * @param string $type
     */
    public function __construct(
        public string $type,
    )
    {
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
