<?php

namespace App\Services\PriceCheck;

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

    /**
     * @return Collection<int, PoeLeagueDto>
     */
    public function pickLeaguesForCheck(): Collection;
}
