<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\Collection;
use OpenEHR\Tools\CodeGen\Model\YamlSerializable;
use Symfony\Component\Yaml\Tag\TaggedValue;

/**
 * Class representing a BMM Integer-based Enumeration
 */
readonly class BmmEnumerationInteger extends AbstractBmmClass implements JsonSerializable, YamlSerializable
{

    /**
     * @param string $name
     * @param string|null $documentation
     * @param array<string>|null $ancestors
     * @param array<string>|null $itemNames
     * @param array<integer>|null $itemValues
     * @param array<string>|null $itemDocumentations
     * @param Collection<string, BmmFunction>|Collection|null $functions
     */
    public function __construct(
        public string $name,
        public ?string $documentation = null,
        public ?array $ancestors = ['Integer'],
        public ?array $itemNames = [],
        public ?array $itemValues = [],
        public ?array $itemDocumentations = [],
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
            '_type' => 'P_BMM_ENUMERATION_INTEGER',
            'name' => $this->name,
            'documentation' => $this->documentation,
            'ancestors' => $this->ancestors,
            'item_names' => $this->itemNames,
            'item_values' => $this->itemValues,
            'item_documentations' => $this->itemDocumentations,
            'functions' => $this->functions->getArrayCopy(),
        ]);
    }

    /**
     * @return TaggedValue
     */
    public function yamlSerialize(): TaggedValue
    {
        return new TaggedValue('P_BMM_ENUMERATION_INTEGER', array_filter([
            'name' => $this->name,
            'documentation' => $this->documentation,
            'ancestors' => $this->ancestors,
            'item_names' => $this->itemNames,
            'item_values' => $this->itemValues,
            'item_documentations' => $this->itemDocumentations,
            'functions' => $this->functions->yamlSerialize(),
        ]));
    }

    /**
     * Create a BmmEnumerationInteger from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            name: $data['name'],
            documentation: $data['documentation'] ?? null,
            ancestors: $data['ancestors'] ?? ['Integer'],
            itemNames: $data['item_names'] ?? [],
            itemValues: $data['item_values'] ?? [],
            itemDocumentations: $data['item_documentations'] ?? [],
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
