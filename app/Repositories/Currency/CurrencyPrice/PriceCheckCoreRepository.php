<?php

namespace App\Repositories\Currency\CurrencyPrice;

use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\Models\Currency\PriceCheckCore;
use App\Models\Currency\PriceCheckCoreType;
use App\Repositories\BaseEloquentRepository;
use App\Repositories\Currency\CurrencyPrice\PriceCheckCoreRepositoryContract;
use App\Services\Mapper\MapperService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @property-read PriceCheckCore $model
 */
class PriceCheckCoreRepository extends BaseEloquentRepository implements PriceCheckCoreRepositoryContract
{
    public function __construct(
        PriceCheckCore $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    /**
     * @return Collection<PriceCheckCoreDto>
     */
    public function getAll(): Collection
    {
        return $this
            ->getQuery()
            ->with('priceCheckCoreTypes')
            ->get()
            ->toBase()
            ->map(fn ($core) => $this->mapper->mapFromArray(PriceCheckCoreDto::class, $core->toArray()));
    }

    /**
     * @return Collection<PriceCheckCoreDto>
     */
    public function getByLeague(int $leagueId): Collection
    {
        return $this
            ->getQuery()
            ->with('priceCheckCoreTypes')
            ->where('poe_league_id', $leagueId)
            ->get()
            ->toBase()
            ->map(fn ($core) => $this->mapper->mapFromArray(PriceCheckCoreDto::class, $core->toArray()));
    }

    public function getLatestByLeague(int $leagueId): PriceCheckCoreDto
    {
        $core = $this
            ->getQuery()
            ->with('priceCheckCoreTypes')
            ->where('poe_league_id', $leagueId)
            ->latest()
            ->first();

        return $this->mapper->mapFromArray(PriceCheckCoreDto::class, $core->toArray());
    }

    /**
     * @return Collection<PriceCheckCoreDto>
     */
    public function getFromDateByLeague(Carbon $dateFrom, int $leagueId): Collection
    {
        return $this
            ->getQuery()
            ->with('priceCheckCoreTypes')
            ->where('poe_league_id', $leagueId)
            ->whereDate('created_at', '>=', $dateFrom)
            ->get()
            ->toBase()
            ->map(fn ($core) => $this->mapper->mapFromArray(PriceCheckCoreDto::class, $core->toArray()));
    }

    public function createCore(
        int $leagueId,
        array $types,
    ): void {
        /** @var PriceCheckCore $core */
        $core = $this->create(['poe_league_id' => $leagueId]);

        $this->createTypes($types, $core);
    }

    private function createTypes(array $types, PriceCheckCore $core): void
    {
        $core
            ->priceCheckCoreTypes()
            ->createMany($types)
        ;
    }
}
