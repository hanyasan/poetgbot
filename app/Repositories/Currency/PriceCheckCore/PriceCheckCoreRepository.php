<?php

namespace App\Repositories\Currency\PriceCheckCore;

use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\Models\Currency\PriceCheckCore;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @property-read PriceCheckCore $model
 */
final class PriceCheckCoreRepository extends BaseEloquentRepository implements PriceCheckCoreRepositoryContract
{
    public function __construct(
        PriceCheckCore $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    #[\Override]
    public function find(int $id): PriceCheckCoreDto
    {
        return $this->mapper->mapFromArray(
            PriceCheckCoreDto::class,
            $this->getQuery()->with('priceCheckCoreTypes')->findOrFail($id)->toArray()
        );
    }

    /**
     * @psalm-return Collection<int, object>
     */
    #[\Override]
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
     * @psalm-return Collection<int, object>
     */
    #[\Override]
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

    #[\Override]
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
     * @psalm-return Collection<int, object>
     */
    #[\Override]
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

//    public function getLatestWithLeague(): Collection
//    {
//        return $this
//            ->getQuery()
//            ->with([
//                'priceCheckCoreTypes',
//                'league',
//            ])
//            ->latest()
//    }

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
    #[\Override]
    public function createCore(
        int $leagueId,
        array $types,
    ): PriceCheckCoreDto {
        /** @var PriceCheckCore $core */
        $core = $this->create(['poe_league_id' => $leagueId]);

        $this->createTypes($types, $core);

        return $this->find($core->id);
    }

    /**
     * @param array{
     *      is_primary: bool,
     *      rate: float,
     *      type_id: string,
     *      name: string,
     * } $types
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
