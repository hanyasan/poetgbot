<?php

namespace App\Http\Resources\Pricing;

use App\DataTransferObjects\ServiceObjects\LeagueLatestPricesDto;
use App\Utility\PricingFormatter;
use Illuminate\Http\Resources\Json\JsonResource;

final class LatestLeaguePricingResource extends JsonResource
{
    #[\Override]
    public function toArray($request): array
    {
        /** @var LeagueLatestPricesDto $resource */
        $resource = $this->resource;

        return [
            'priced_at' => $resource->core->created_at,
            'priced_currency' => PricingFormatter::formatPrices(
                $resource->core->price_check_core_types,
                $resource->prices,
            ),
        ];
    }
}
