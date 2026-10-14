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
     * @param array{
     *      is_primary: bool,
     *      rate: float,
     *      type_id: string,
     *      name: string,
     * } $types
     *
     * @return PriceCheckCoreDto
     */
    public function createCore(
        int $leagueId,
        array $types,
    ): PriceCheckCoreDto;
}
