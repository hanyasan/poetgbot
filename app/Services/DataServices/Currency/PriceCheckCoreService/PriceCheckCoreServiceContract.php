<?php

namespace App\Services\DataServices\Currency\PriceCheckCoreService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface PriceCheckCoreServiceContract
{
    public function find(int $id): PriceCheckCoreDto;

    /**
     * @return Collection<int, PriceCheckCoreDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<int, PriceCheckCoreDto>
     */
    public function getByLeague(int $leagueId): Collection;

    public function getLatestByLeague(int $leagueId): PriceCheckCoreDto;

    /**
     * @return Collection<int, PriceCheckCoreDto>
     */
    public function getFromDateByLeague(Carbon $dateFrom, int $leagueId): Collection;

    /**
     * @param array{
     *     types: array{
     *          is_primary: bool,
     *          rate: float,
     *          type_id: string,
     *          name: string,
     *     }
     * } $coreData
     * @param int $leagueId
     *
     * @return PriceCheckCoreDto
     */
    public function create(array $coreData, int $leagueId): PriceCheckCoreDto;

    public function createNewByNinja(int $leagueId): PriceCheckCoreDto;

    /**
     * @param PoeNinjaExchangeResponse $prices

     * @return array{
     *     is_primary: bool,
     *     rate: float,
     *     type_id: string,
     *     name: string,
     * }
     */
    public function prepTypes(PoeNinjaExchangeResponse $prices): array;

    public function flushAll(): void;

    public function flushLeagueId(int $leagueId): void;

}
