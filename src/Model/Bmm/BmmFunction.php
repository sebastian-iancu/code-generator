<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use OpenEHR\Tools\CodeGen\Model\Collection;

/**
 * Class representing a BMM function
 */
readonly class BmmFunction implements JsonSerializable, CollectableInterface
{
    use CollectableTrait;

    /**
     * @param string $name
     * @param string|null $documentation
     * @param Collection<string, AbstractBmmFunctionParameter>|null $parameters
     * @param array|null $preConditions
     * @param array|null $postConditions
     * @param AbstractBmmType|null $result
     */
    public function __construct(
        public string $name,
        public ?string $documentation = null,
        public ?Collection $parameters = new Collection(),
        public ?array $preConditions = [],
        public ?array $postConditions = [],
        public ?AbstractBmmType $result = null,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'documentation' => $this->documentation,
            'parameters' => $this->parameters->getArrayCopy(),
            'pre_conditions' => $this->preConditions,
            'post_conditions' => $this->postConditions,
            'result' => $this->result,
        ]);

    }

    /**
     * Create a BMMFunction from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            name: $data['name'],
            documentation: $data['documentation'] ?? null,
            parameters: new Collection(),
            preConditions: $data['pre_conditions'] ?? [],
            postConditions: $data['post_conditions'] ?? [],
            result: isset($data['result']) ? AbstractBmmType::fromArray($data['result']) : null,
        );

        if (!empty($data['parameters']) && is_iterable($data['parameters'])) {
            array_walk($data['parameters'], function ($parameterData, $parameterName) use ($instance) {
                $instance->parameters->offsetSet($parameterName, AbstractBmmFunctionParameter::fromArray($parameterData));
            });
        }

        return $instance;
    }
}
