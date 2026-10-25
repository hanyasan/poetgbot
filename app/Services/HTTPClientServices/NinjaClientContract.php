<?php

namespace App\Services\HTTPClientServices;

use App\Enums\PoeVersionEnum;
use GuzzleHttp\Exception\GuzzleException;

interface NinjaClientContract
{
    /**
     * @throws GuzzleException
     */
    public function getCurrencyPrices(PoeVersionEnum $version, string $league): string;

    /**
     * @throws GuzzleException
     */
    public function getAvailableLeagues(PoeVersionEnum $version): string;
}
