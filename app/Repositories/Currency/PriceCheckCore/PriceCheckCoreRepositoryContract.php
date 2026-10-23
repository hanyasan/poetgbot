<?php

namespace App\Repositories\Currency\PriceCheckCore;

use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface PriceCheckCoreRepositoryContract
{
    public function find(int $id): PriceCheckCoreDto;

    /**
     * @return Collection<int, PriceCheckCoreDto>
     */
    public function getAll(): Collection;

    /** @param int[] $ids */
    public function deleteMany(array $ids): void;

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
     * @param int $leagueId
     * @param array<int, array{
     *      is_primary: bool,
     *      rate: float,
     *      type_id: string,
     *      name: string,
     * }> $types
     *
     * @return PriceCheckCoreDto
     */
    public function createCore(
        int $leagueId,
        array $types,
    ): PriceCheckCoreDto;

    /**
     * @param array<int, int> $coreIds
     * @return array<int, array{
     *     type_id: string,
     *     type_name: string,
     *     avg_type_rate: float,
     * }>
     */
    public function getAvgCoreTypesData(array $coreIds): array;

    /**
     * @param Carbon $date
     * @return array<int, array{
     *     id: int,
     *     poe_league_id: int,
     *     date: string,
     * }>
     */
    public function getLeagueDailyPeriodsCoreIds(Carbon $date): array;
}
