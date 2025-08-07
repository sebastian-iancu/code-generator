<?php

namespace OpenEHR\Tools\CodeGen\Model;

use ArrayObject;
use JsonSerializable;

/**
 * @template-extends ArrayObject<string, CollectableInterface>
 */
class Collection extends ArrayObject implements JsonSerializable
{

    /** @var array<string, string> */
    public array $aliases = [];

    public function add(CollectableInterface $item, ?string $additionalAlias = null): void
    {
        $key = $item->getName() ?: get_class($item);
        $this->offsetSet($key, $item);
        if ($item->getAlias()) {
            $this->aliases[$item->getAlias()] = $key;
        }
        if ($additionalAlias) {
            $this->aliases[$additionalAlias] = $key;
        }
    }

    public function get(string $key): ?CollectableInterface
    {
        $key = $this->aliases[$key] ?? $key;
        return $this->offsetExists($key) ? $this->offsetGet($key) : null;
    }

    public function flush(): void
    {
        $this->aliases = [];
        $this->exchangeArray([]);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->aliases ? array_merge([
            '__aliases' => $this->aliases,
        ], $this->getArrayCopy()) : $this->getArrayCopy();
    }

    /**
     * @return array<string, mixed>
     */
    public function yamlSerialize(): array
    {
        return array_map(fn($item) => $item->yamlSerialize(), $this->getArrayCopy());
    }
}
