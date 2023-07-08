<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\Currency\PoeNinjaCurrencyResponse;

interface PoeNinjaServiceContract
{
    public function getCurrencyPrices(): PoeNinjaCurrencyResponse;
}
