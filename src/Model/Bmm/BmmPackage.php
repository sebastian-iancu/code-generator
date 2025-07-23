<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\CollectableInterface;
use OpenEHR\Tools\CodeGen\Model\Collection;

/**
 * Class representing a BMM package
 */
readonly class BmmPackage implements JsonSerializable, CollectableInterface
{

    use CollectableTrait;

    /**
     * @param string $name
     * @param Collection<string, BmmPackage>|null $packages
     * @param array|null $classes
     */
    public function __construct(
        public string $name,
        public ?Collection $packages = new Collection(),
        public ?array $classes = []
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
            'packages' => $this->packages->getArrayCopy(),
            'classes' => $this->classes,
        ]);
    }

    /**
     * Create a BMMPackage from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            name: $data['name'],
            packages: new Collection(),
            classes: $data['classes'] ?? [],
        );
        if (!empty($data['packages']) && is_iterable($data['packages'])) {
            array_walk($data['packages'], function ($packageData) use ($instance) {
                $instance->packages->add(BmmPackage::fromArray($packageData));
            });
        }

        return $instance;
    }

}
