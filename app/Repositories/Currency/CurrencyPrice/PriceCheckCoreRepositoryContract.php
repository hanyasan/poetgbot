<?php

namespace App\Repositories\Currency\CurrencyPrice;

use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\Models\Currency\PriceCheckCore;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface PriceCheckCoreRepositoryContract
{
    /**
     * @return Collection<PriceCheckCoreDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<PriceCheckCoreDto>
     */
    public function getByLeague(int $leagueId): Collection;

    public function getLatestByLeague(int $leagueId): PriceCheckCoreDto;

    /**
     * @return Collection<PriceCheckCoreDto>
     */
    public function getFromDateByLeague(Carbon $dateFrom, int $leagueId): Collection;

    public function createCore(
        int $leagueId,
        array $types,
    ): void;
}
