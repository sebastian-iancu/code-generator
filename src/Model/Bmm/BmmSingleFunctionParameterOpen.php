<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\YamlSerializable;
use Symfony\Component\Yaml\Tag\TaggedValue;

/**
 * Class representing a BMM single function parameter
 */
readonly class BmmSingleFunctionParameterOpen extends AbstractBmmFunctionParameter implements JsonSerializable, YamlSerializable
{

    /**
     * @param string $name
     * @param string $type
     * @param string|null $documentation
     * @param bool|null $isNullable
     */
    public function __construct(
        public string $name,
        public string $type,
        public ?string $documentation = null,
        public ?bool $isNullable = false,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            '_type' => 'P_BMM_SINGLE_FUNCTION_PARAMETER_OPEN',
            'name' => $this->name,
            'documentation' => $this->documentation,
            'is_nullable' => $this->isNullable,
            'type' => $this->type,
        ]);
    }

    /**
     * @return TaggedValue
     */
    public function yamlSerialize(): TaggedValue
    {
        return new TaggedValue('P_BMM_SINGLE_FUNCTION_PARAMETER_OPEN', array_filter([
            'name' => $this->name,
            'documentation' => $this->documentation,
            'is_nullable' => $this->isNullable,
            'type' => $this->type,
        ]));
    }

    /**
     * Create a BMMSingleFunctionParameter from a JSON array
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
            isNullable: $data['is_nullable'] ?? false,
        );
    }
}
