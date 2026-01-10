<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\YamlSerializable;
use Symfony\Component\Yaml\Tag\TaggedValue;

/**
 * Class representing a BMM single property
 */
readonly class BmmSingleProperty extends AbstractBmmProperty implements JsonSerializable, YamlSerializable
{

    /**
     * @param string $name
     * @param string $type
     * @param string|null $documentation
     * @param bool|null $isMandatory
     * @param mixed $default
     */
    public function __construct(
        public string $name,
        public string $type,
        public ?string $documentation = null,
        public ?bool $isMandatory = false,
        public mixed $default = null,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $result = array_filter([
            '_type' => 'P_BMM_SINGLE_PROPERTY',
            'name' => $this->name,
            'documentation' => $this->documentation,
            'is_mandatory' => $this->isMandatory,
            'type' => $this->type,
        ]);
        if ($this->default !== null) {
            $result['default'] = $this->default;
        }
        return $result;
    }

    /**
     * @return TaggedValue
     */
    public function yamlSerialize(): TaggedValue
    {
        $result = array_filter([
            'name' => $this->name,
            'documentation' => $this->documentation,
            'is_mandatory' => $this->isMandatory,
            'type' => $this->type,
            'default' => $this->default,
        ]);
        if ($this->default !== null) {
            $result['default'] = $this->default;
        }
        return new TaggedValue('P_BMM_SINGLE_PROPERTY', $result);
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
            type: $data['type'] ?? 'Any',
            documentation: $data['documentation'] ?? null,
            isMandatory: $data['is_mandatory'] ?? false,
            default: $data['default'] ?? null,
        );
    }
}
