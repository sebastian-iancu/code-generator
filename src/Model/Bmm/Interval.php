<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use JsonSerializable;

readonly class Interval implements JsonSerializable
{

    /**
     * @param int|null $lower
     * @param int|null $upper
     * @param bool|null $lowerUnbounded
     * @param bool|null $upperUnbounded
     */
    public function __construct(
        public ?int $lower = 0,
        public ?int $upper = null,
        public ?bool $lowerUnbounded = false,
        public ?bool $upperUnbounded = true,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'lower' => $this->lower,
            'upper' => $this->upper,
            'lower_unbounded' => $this->lowerUnbounded,
            'upper_unbounded' => $this->upperUnbounded,
        ], function (mixed $value): bool { // to force export default |0..*|
            return !is_null($value) && $value !== false;
        });
    }

    public static function fromArray(array $data): self
    {
        return new self(
            lower: $data['lower'] ?? 0,
            upper: $data['upper'] ?? null,
            lowerUnbounded: $data['lower_unbounded'] ?? false,
            upperUnbounded: $data['upper_unbounded'] ?? true,
        );
    }
}
