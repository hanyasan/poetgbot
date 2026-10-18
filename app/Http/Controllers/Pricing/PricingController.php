<?php

namespace App\Http\Controllers\Pricing;

use App\Enums\PoeVersionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\PricingLatestRequest;
use App\Http\Resources\Pricing\LatestLeaguePricingResource;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\Pricing\PricingServiceContract;
use Illuminate\Http\JsonResponse;

final class PricingController extends Controller
{
    public function __construct(
        private readonly PoeLeagueServiceContract $poeLeagueService,
        private readonly PricingServiceContract $pricingService,
    ) {}

    public function latest(PricingLatestRequest $request): JsonResponse
    {
        $league = $this->poeLeagueService->findActiveByNameVersion(
            $request->league_name,
            PoeVersionEnum::from($request->poe_version)
        );

        return $this->response(LatestLeaguePricingResource::make(
            $this->pricingService->getLeagueLatestPrices($league->id)
        ));
    }
}
