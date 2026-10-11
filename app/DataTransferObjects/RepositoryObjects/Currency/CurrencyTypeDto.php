<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

class CurrencyTypeDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $currency_type_name,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?string $currency_trade_id = null,
        public readonly ?string $currency_ninja_details_id = null,
    ) {}
}
