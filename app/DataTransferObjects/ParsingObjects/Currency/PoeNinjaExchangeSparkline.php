<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaExchangeSparkline
{
    public function __construct(
        public ?float $totalChange,
        /** @var array<int, float|null> */
        public array  $data,
    ) {}
}
