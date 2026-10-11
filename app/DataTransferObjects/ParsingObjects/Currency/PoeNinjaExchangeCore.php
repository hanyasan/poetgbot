<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaExchangeCore
{
    public function __construct(
        /** @var PoeNinjaExchangeItem[] */
        public array  $items,
        /** @var array<string, float> */
        public array  $rates,
        public string $primary,
        public string $secondary,
    ) {}
}
