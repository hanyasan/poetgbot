<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Resources\Json\JsonResource;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function response(JsonResource|array|string|int|float|null $responseData = null): JsonResponse
    {
        return \response()->json([
            'success' => true,
            'data' => is_null($responseData) ? 'ok' : $responseData,
        ]);
    }
}
