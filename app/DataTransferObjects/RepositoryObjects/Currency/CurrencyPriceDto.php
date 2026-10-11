<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

final readonly class CurrencyPriceDto
{
    public function __construct(
        public int   $id,
        public float $rate,
    ) {}
}
