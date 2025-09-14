<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\YamlSerializable;
use Symfony\Component\Yaml\Tag\TaggedValue;


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
            '_type' => 'P_BMM_SIMPLE_TYPE',
            'type' => $this->type,
        ];
    }

    /**
     * @return TaggedValue
     */
    public function yamlSerialize(): TaggedValue
    {
        return new TaggedValue('P_BMM_SIMPLE_TYPE', array_filter([
            'type' => $this->type,
        ]));
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
