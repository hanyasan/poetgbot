<?php

namespace App\Http\Controllers\Price;

use Illuminate\Routing\Controller as BaseController;

class CurrencyController extends BaseController
{
    public function showPrice(
//        CurrencyPriceServiceContract $currencyPriceService,
        string $ninjaDetailsId
    ) {
//        return response()->okResponse(CurrencyPriceResource::make(
//            $currencyPriceService->findByTypeDetailId($ninjaDetailsId)
//        ));
        return response();
    }
}
