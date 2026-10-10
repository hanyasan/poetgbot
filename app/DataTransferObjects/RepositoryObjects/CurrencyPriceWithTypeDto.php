<?php

namespace App\DataTransferObjects\RepositoryObjects;

use Illuminate\Support\Carbon;

class CurrencyPriceWithTypeDto
{
    public function __construct(
        public readonly int $id,
        public readonly float $chaos_equivalent,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        /** @var CurrencyTypeWithLeagueDto */
        public readonly object $type,
        public readonly int $currency_type_id,
        public readonly ?float $sell_price = null,
        public readonly ?float $buy_price = null,
    ) {}
}
