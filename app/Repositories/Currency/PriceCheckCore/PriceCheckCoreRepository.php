<?php

namespace App\Repositories\Currency\PriceCheckCore;

use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\Models\Currency\PriceCheckCore;
use App\Services\Mapper\MapperService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
