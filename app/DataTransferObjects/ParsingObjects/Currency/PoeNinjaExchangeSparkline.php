<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final class PoeNinjaExchangeSparkline
{
    public function __construct(
        public readonly ?float $totalChange,
        /** @var array<int, float|null> */
        public readonly array $data,
    ) {}
}
