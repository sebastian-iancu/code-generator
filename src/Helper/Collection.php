<?php

namespace OpenEHR\Tools\CodeGen\Helper;

use ArrayObject;
use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\AbstractItem;

/**
 * @template-extends ArrayObject<string, AbstractItem>
 */
class Collection extends ArrayObject implements JsonSerializable
{

    /** @var array<string, string> */
    public array $aliases = [];

    public function add(AbstractItem $item, ?string $alias = null): void
    {
        $key = $item->name;
        $this->offsetSet($key, $item);
        $alias ??= $item->id ?? null;
        if ($alias) {
            $this->aliases[$alias] = $key;
        }
    }

    public function get(string $key): ?AbstractItem
    {
        $key = $this->aliases[$key] ?? $key;
        return $this->offsetGet($key) ?: null;
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
}
