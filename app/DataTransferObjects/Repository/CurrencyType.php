<?php

namespace App\DataTransferObjects\Repository;

use Illuminate\Support\Carbon;

class CurrencyType
{
    public function __construct(
        public readonly int $id,
        public readonly string $currency_type_name,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        public readonly ?string $currency_trade_id = null,
        public readonly ?string $currency_ninja_details_id = null,
    ) {}
}
