<?php

namespace App\DataTransferObjects\Currency;

final class PoeNinjaExchangeCore
{
    public function __construct(
        /** @var PoeNinjaExchangeItem[] */
        public readonly array $items,
        /** @var array<string, float> */
        public readonly array $rates,
        public readonly string $primary,
        public readonly string $secondary,
    ) {}
}
