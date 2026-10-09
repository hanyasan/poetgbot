<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\PoeNinjaVersionResponse;
use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClientContract;

interface PoeNinjaServiceContract
{
    public function getCurrencyPrices(
        PoeVersionEnum $version,
        string $league,
    ): PoeNinjaExchangeResponse;

    public function getAvailableLeagues(
        PoeVersionEnum $version,
    ): PoeNinjaVersionResponse;
}
