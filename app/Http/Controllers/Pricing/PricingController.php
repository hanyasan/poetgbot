<?php

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\PricingLatestRequest;
use Illuminate\Http\JsonResponse;

final class PricingController extends Controller
{
    public function latest(PricingLatestRequest $request): JsonResponse
    {
        return $this->response();
    }
}
