<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\Currency\PoeNinjaExchangeResponse;
use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClientContract;

interface PoeNinjaServiceContract
{
    public function getCurrencyPrices(
        PoeVersionEnum $version,
        string $league,
        NinjaClientContract $client,
    ): PoeNinjaExchangeResponse;
}
