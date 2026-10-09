<?php

namespace App\DataTransferObjects\RepositoryObjects;

use Illuminate\Support\Carbon;

class CurrencyTypeWithLeague
{
    public function __construct(
        public readonly int $id,
        public readonly string $currency_type_name,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        /** @var PoeLeague */
        public readonly object $league,
        public readonly ?string $currency_trade_id = null,
        public readonly ?string $currency_ninja_details_id = null,
    ) {}
}
