<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Http\Resources\PoeLeagueResource;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use Illuminate\Http\JsonResponse;

/** @psalm-suppress UnusedClass */
final class LeagueController extends Controller
{
    public function __construct(
        private readonly PoeLeagueServiceContract  $poeLeagueService,
    )
    {}
    public function index(): JsonResponse
    {
        return $this->response(
            PoeLeagueResource::collection($this->poeLeagueService->getAllActive())
        );
    }
}
