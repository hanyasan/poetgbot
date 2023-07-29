<?php

namespace App\DataTransferObjects\Repository;

use Illuminate\Support\Carbon;

class CurrencyPriceWithType
{
    public function __construct(
        public readonly int $id,
        public readonly float $chaos_equivalent,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        /** @var CurrencyType */
        public readonly object $type,
        public readonly int $currency_type_id,
        public readonly ?float $sell_price = null,
        public readonly ?float $buy_price = null,
    ) {}
}
