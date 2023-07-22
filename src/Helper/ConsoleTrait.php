<?php

namespace OpenEHR\Tools\CodeGen\Helper;

trait ConsoleTrait {

    public function log(string $message, bool|float|int|string|null ...$variables): static {
        $parts = explode('\\', static::class);
        $prefix = str_pad(array_pop($parts) . '-' . array_pop($parts), 25) . ': ';
        echo sprintf($prefix . $message, ...$variables) . PHP_EOL;
        return $this;
    }

}
