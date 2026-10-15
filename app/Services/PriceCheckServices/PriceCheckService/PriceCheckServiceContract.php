<?php

namespace App\Services\PriceCheckServices\PriceCheckService;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\ServiceObjects\LeaguePricesDto;
use Illuminate\Support\Collection;

interface PriceCheckServiceContract
{
    public function importLeagueLatestPrices(PoeLeagueDto $league): LeaguePricesDto;

    /**
     * @return Collection<int, LeaguePricesDto>
     */
    public function importPickedLeaguesPrices(): Collection;
}
