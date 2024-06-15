<?php

namespace App\Http\Controllers\Price;

use App\Http\Resources\CurrencyPriceResource;
use App\Services\DataServices\CurrencyPriceService\CurrencyPriceServiceContract;
use Illuminate\Routing\Controller as BaseController;

class CurrencyController extends BaseController
{
    public function showPrice(
        CurrencyPriceServiceContract $currencyPriceService,
        string $ninjaDetailsId
    ) {
        return response()->okResponse(CurrencyPriceResource::make(
            $currencyPriceService->findByTypeDetailId($ninjaDetailsId)
        ));
    }
}
