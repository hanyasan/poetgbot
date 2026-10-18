<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\ServiceObjects\LeagueLatestPricesDto;

interface PricingServiceContract
{
    /**
     * @param int $leagueId
     */
    public function getLeagueLatestPrices(int $leagueId): LeagueLatestPricesDto;
}
