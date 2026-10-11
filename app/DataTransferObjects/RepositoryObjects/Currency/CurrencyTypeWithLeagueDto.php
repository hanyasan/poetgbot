<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use Illuminate\Support\Carbon;

class CurrencyTypeWithLeagueDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $currency_type_name,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        /** @var PoeLeagueDto */
        public readonly object $league,
        public readonly ?string $currency_trade_id = null,
        public readonly ?string $currency_ninja_details_id = null,
    ) {}
}
