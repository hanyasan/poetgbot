<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\ParsingObjects\PoeNinjaLeagueResponse;
use App\Enums\PoeVersionEnum;

interface PoeNinjaServiceContract
{
    public function getCurrencyPrices(
        PoeVersionEnum $version,
        string         $league,
    ): PoeNinjaExchangeResponse;

    public function getAvailableLeagues(
        PoeVersionEnum $version,
    ): PoeNinjaLeagueResponse;
}
