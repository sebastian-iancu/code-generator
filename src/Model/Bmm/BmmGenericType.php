<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use OpenEHR\Tools\CodeGen\Model\Collection;


readonly class BmmGenericType implements JsonSerializable, CollectableInterface
{

    private string $name;

    use CollectableTrait;

    /**
     * @param string $rootType
     * @param Collection<string, BmmContainerType|BmmGenericType|BmmSimpleType>|Collection|null $genericParameterDefs
     * @param array<string> $genericParameters
     */
    public function __construct(
        public string $rootType,
        public ?Collection $genericParameterDefs = new Collection(),
        public ?array $genericParameters = [],
    )
    {
        $this->name = '';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            '_type' => 'P_BMM_GENERIC_TYPE',
            'root_type' => $this->rootType,
            'generic_parameter_defs' => $this->genericParameterDefs->getArrayCopy(),
            'generic_parameters' => $this->genericParameters,
        ]);
    }

    /**
     * Create a BMMGenericType from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            rootType: $data['root_type'],
            genericParameterDefs: new Collection(),
            genericParameters: $data['generic_parameters'] ?? [],
        );
        if (!empty($data['generic_parameter_defs']) && is_iterable($data['generic_parameter_defs'])) {
            array_walk($data['generic_parameter_defs'], function ($genericParameterDefData, $key) use ($instance) {
                $instance->genericParameterDefs->offsetSet($key, AbstractBmmType::fromArray($genericParameterDefData));
            });
        }
        return $instance;
    }
}
