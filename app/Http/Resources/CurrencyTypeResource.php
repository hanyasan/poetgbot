<?php

namespace App\Http\Resources;

use App\DataTransferObjects\Repository\CurrencyType;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var CurrencyType $type */
        $type = $this->resource;

        return [
         'currency_type_name' => $type->currency_type_name,
         'currency_trade_id' => $type->currency_trade_id,
         'currency_ninja_details_id' => $type->currency_ninja_details_id,
        ];
    }
}
