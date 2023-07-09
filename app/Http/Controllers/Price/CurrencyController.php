<?php

namespace App\Http\Controllers\Price;

use App\Models\Currency\CurrencyPrice;
use App\Models\Currency\CurrencyType;
use App\Services\PoeNinjaService\PoeNinjaService;
use Illuminate\Routing\Controller as BaseController;

class CurrencyController extends BaseController
{
    public function showPrice(string $ninjaDetailsId)
    {
        $currencyType = CurrencyType::where('currency_ninja_details_id', $ninjaDetailsId)->firstOrFail();

        return response()->json(
            CurrencyPrice::where('currency_type_id', $currencyType->id)->firstOrFail()
        );
    }
}
