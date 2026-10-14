<?php

namespace App\Http\Controllers\Price;

use Illuminate\Routing\Controller as BaseController;

final class CurrencyController extends BaseController
{
    public function showPrice(
//        CurrencyPriceServiceContract $currencyPriceService,
        string $ninjaDetailsId
    ): \Illuminate\Contracts\Routing\ResponseFactory {
//        return response()->okResponse(CurrencyPriceResource::make(
//            $currencyPriceService->findByTypeDetailId($ninjaDetailsId)
//        ));
        return response();
    }
}
