<?php

namespace App\Services\PriceCheckServices\PriceCheckService;

use App\DataTransferObjects\ServiceObjects\LeaguePricesDto;

interface PriceCheckServiceContract
{
    public function importLeagueLatestPrices(int $leagueId): LeaguePricesDto;
}
