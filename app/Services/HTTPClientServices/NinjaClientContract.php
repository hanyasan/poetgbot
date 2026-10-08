<?php

namespace App\Services\HTTPClientServices;

use App\Enums\PoeVersionEnum;

interface NinjaClientContract
{
    public function getCurrencyPrices(PoeVersionEnum $version, string $league): string;
}
