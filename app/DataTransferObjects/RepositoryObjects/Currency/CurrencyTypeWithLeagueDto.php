<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;

final readonly class CurrencyTypeWithLeagueDto
{
    public function __construct(
        public int     $id,
        public string  $currency_type_name,
        public string  $created_at,
        public string  $updated_at,
        /** @var PoeLeagueDto */
        public object  $league,
        public ?string $currency_trade_id = null,
        public ?string $currency_ninja_details_id = null,
    ) {}
}
