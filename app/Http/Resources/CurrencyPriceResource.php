<?php

namespace App\Http\Resources;

use App\DataTransferObjects\Repository\CurrencyPriceWithType;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyPriceResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var CurrencyPriceWithType $price */
        $price = $this->resource;

        return [
            'chaos_equivalent' => $price->chaos_equivalent,
            'updated_at' => $price->updated_at?->toDateTimeString(),
            'sell_price' => round($price->sell_price ?? 0, 2),
            'buy_price' => round($price->buy_price ?? 0, 2),
            'type' => CurrencyTypeResource::make($price->type),
        ];
    }
}
