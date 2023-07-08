<?php

namespace App\Http\Controllers\Price;

use App\Services\PoeNinjaService\PoeNinjaService;
use Illuminate\Routing\Controller as BaseController;

class DivineController extends BaseController
{
    public function showPrices()
    {
        return response()->json(
            app(PoeNinjaService::class)->getCurrencyPrices()
        );
    }
}
