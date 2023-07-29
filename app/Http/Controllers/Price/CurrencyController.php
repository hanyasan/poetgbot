<?php

namespace App\Http\Controllers\Price;

use App\Services\DataServices\CurrencyPriceService\CurrencyPriceServiceContract;
use Illuminate\Routing\Controller as BaseController;

class CurrencyController extends BaseController
{
    public function showPrice(
        CurrencyPriceServiceContract $currencyPriceService,
        string $ninjaDetailsId
    )
    {
        return response()->json(
            $currencyPriceService->findByTypeDetailId($ninjaDetailsId)
        );
    }
}
