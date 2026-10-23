<?php

namespace App\Repositories\Currency\PriceCheckCore;

use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\Models\Currency\PriceCheckCore;
use App\Models\Currency\PriceCheckCoreType;
use App\Services\Mapper\MapperService;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property-read PriceCheckCore $model
 */
final class PriceCheckCoreRepository implements PriceCheckCoreRepositoryContract
{
    public function __construct(
        private readonly PriceCheckCore $model,
        private readonly MapperService $mapper
    ) {}

    #[\Override]
    public function find(int $id): PriceCheckCoreDto
    {
        return $this->mapper->mapFromArray(
            PriceCheckCoreDto::class,
            $this->model->newQuery()->with('priceCheckCoreTypes')->findOrFail($id)->toArray()
        );
    }

    /**
     * @psalm-return Collection<int, PriceCheckCoreDto>
     */
    #[\Override]
    public function getAll(): Collection
    {
        return $this
            ->model
            ->newQuery()
            ->with('priceCheckCoreTypes')
            ->get()
            ->toBase()
            ->map(fn ($core) => $this->mapper->mapFromArray(PriceCheckCoreDto::class, $core->toArray()));
    }

    /** @param int[] $ids */
    #[\Override]
    public function deleteMany(array $ids): void {
        $this
            ->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * @psalm-return Collection<int, PriceCheckCoreDto>
     */
    #[\Override]
    public function getByLeague(int $leagueId): Collection
    {
        return $this
            ->model
            ->newQuery()
            ->with('priceCheckCoreTypes')
            ->where('poe_league_id', $leagueId)
            ->get()
            ->toBase()
            ->map(fn ($core) => $this->mapper->mapFromArray(PriceCheckCoreDto::class, $core->toArray()));
    }

    #[\Override]
    public function getLatestByLeague(int $leagueId): PriceCheckCoreDto
    {
        $core = $this
            ->model
            ->newQuery()
            ->with('priceCheckCoreTypes')
            ->where('poe_league_id', $leagueId)
            ->latest()
            ->firstOrFail();

        return $this->mapper->mapFromArray(PriceCheckCoreDto::class, $core->toArray());
    }

    /**
     * @psalm-return Collection<int, PriceCheckCoreDto>
     */
    #[\Override]
    public function getFromDateByLeague(Carbon $dateFrom, int $leagueId): Collection
    {
        return $this
            ->model
            ->newQuery()
            ->with('priceCheckCoreTypes')
            ->where('poe_league_id', $leagueId)
            ->whereDate('created_at', '>=', $dateFrom)
            ->get()
            ->toBase()
            ->map(fn ($core) => $this->mapper->mapFromArray(PriceCheckCoreDto::class, $core->toArray()));
    }

    /**
     * @param array<int, int> $coreIds
     * @return array<int, array{
     *     type_id: string,
     *     type_name: string,
     *     avg_type_rate: float,
     * }>
     */
    #[\Override]
    public function getAvgCoreTypesData(array $coreIds): array
    {
        $sub = PriceCheckCoreType::query()
            ->select([
                'type_id',
                DB::raw('AVG(price_check_core_types.rate) as avg_type_rate'),
            ])
            ->whereIn('price_check_core_types.price_check_core_id', $coreIds)
            ->groupBy('price_check_core_types.type_id');

        return PriceCheckCoreType::query()
            ->select([
                'price_check_core_types.type_id',
                'price_check_core_types.name as type_name',
                'avg_type_rate'
            ])
            ->distinct()
            ->rightJoinSub($sub, 'sub_ct', function (JoinClause $join) {
                $join->on('sub_ct.type_id', '=', 'price_check_core_types.type_id');
            })
            ->get()
            ->toBase()
            ->toArray();
    }

    /**
     * @param Carbon $date
     * @return array<int, array{
     *     id: int,
     *     poe_league_id: int,
     *     date: string,
     * }>
     */
    #[\Override]
    public function getLeagueDailyPeriodsCoreIds(Carbon $date): array
    {
        return $this->model->newQuery()
            ->select([
                'id',
                'poe_league_id',
                DB::raw("date(price_check_cores.created_at AT TIME ZONE 'UTC') as date"),
            ])
            ->whereDate(DB::raw("date(price_check_cores.created_at AT TIME ZONE 'UTC')"), '<=', $date->setTimezone('UTC'))
            ->get()
            ->toArray();
    }

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
    #[\Override]
    public function createCore(
        int $leagueId,
        array $types,
    ): PriceCheckCoreDto {
        /** @var PriceCheckCore $core */
        $core = $this->model->newQuery()->create(['poe_league_id' => $leagueId]);

        $this->createTypes($types, $core);

        return $this->find($core->id);
    }

    /**
     * @param array<int, array{
     *      is_primary: bool,
     *      rate: float,
     *      type_id: string,
     *      name: string,
     * }> $types
     * @param PriceCheckCore $core
     *
     * @return void
     */
    private function createTypes(array $types, PriceCheckCore $core): void
    {
        $core
            ->priceCheckCoreTypes()
            ->createMany($types)
        ;
    }
}
