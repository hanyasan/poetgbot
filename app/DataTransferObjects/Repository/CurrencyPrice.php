<?php

namespace App\DataTransferObjects\Repository;

use Illuminate\Support\Carbon;

class CurrencyPrice
{
    public function __construct(
        public readonly int $id,
        public readonly float $chaos_equivalent,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        public readonly int $currency_type_id,
        public readonly ?float $sell_price = null,
        public readonly ?float $buy_price = null,
    ) {}
}
