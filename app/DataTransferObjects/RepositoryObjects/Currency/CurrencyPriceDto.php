<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

class CurrencyPriceDto
{
    public function __construct(
        public readonly int $id,
        public readonly float $chaos_equivalent,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly int $currency_type_id,
        public readonly ?float $sell_price = null,
        public readonly ?float $buy_price = null,
    ) {}
}
