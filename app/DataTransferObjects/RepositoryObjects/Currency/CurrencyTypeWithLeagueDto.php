<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;

class CurrencyTypeWithLeagueDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $currency_type_name,
        public readonly string $created_at,
        public readonly string $updated_at,
        /** @var PoeLeagueDto */
        public readonly object $league,
        public readonly ?string $currency_trade_id = null,
        public readonly ?string $currency_ninja_details_id = null,
    ) {}
}
