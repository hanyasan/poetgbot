<?php

namespace App\Services\PriceCheckServices;

use App\DataTransferObjects\ServiceObjects\LeaguePricesDto;

interface PriceCheckServiceContract
{
    public function importLeagueLatestPrices(int $leagueId): LeaguePricesDto;
}
