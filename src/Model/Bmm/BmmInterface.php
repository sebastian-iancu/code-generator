<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\Collection;
use OpenEHR\Tools\CodeGen\Model\YamlSerializable;
use Symfony\Component\Yaml\Tag\TaggedValue;

/**
 * Class representing a BMM Interface definition
 */
readonly class BmmInterface extends AbstractBmmClass implements JsonSerializable, YamlSerializable
{

    /**
     * @param string $name
     * @param string|null $documentation
     * @param Collection<string, BmmFunction>|Collection|null $functions
     */
    public function __construct(
        public string $name,
        public ?string $documentation = null,
        public ?Collection $functions = new Collection(),
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            '_type' => 'P_BMM_INTERFACE',
            'name' => $this->name,
            'documentation' => $this->documentation,
            'functions' => $this->functions->getArrayCopy(),
        ]);

    }

    /**
     * @return TaggedValue
     */
    public function yamlSerialize(): TaggedValue
    {
        return new TaggedValue('P_BMM_INTERFACE', array_filter([
            'name' => $this->name,
            'documentation' => $this->documentation,
            'functions' => $this->functions->yamlSerialize(),
        ]));
    }

    /**
     * Create a BMMInterface from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            name: $data['name'],
            documentation: $data['documentation'] ?? null,
            functions: new Collection(),
        );

        if (!empty($data['functions']) && is_iterable($data['functions'])) {
            array_walk($data['functions'], function ($functionData) use ($instance) {
                $instance->functions->add(BmmFunction::fromArray($functionData));
            });
        }

        return $instance;
    }
}
