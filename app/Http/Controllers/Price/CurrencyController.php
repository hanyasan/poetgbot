<?php

namespace App\Http\Controllers\Price;

use App\Models\Currency\CurrencyPrice;
use App\Services\DataServices\CurrencyTypeService\CurrencyTypeServiceContract;
use Illuminate\Routing\Controller as BaseController;

class CurrencyController extends BaseController
{
    public function showPrice(
        CurrencyTypeServiceContract $currencyTypeService,
        string $ninjaDetailsId
    )
    {
        return response()->json(
            CurrencyPrice::where(
                'currency_type_id',
                $currencyTypeService->findByDetail($ninjaDetailsId)->id
            )->firstOrFail()
        );
    }
}
