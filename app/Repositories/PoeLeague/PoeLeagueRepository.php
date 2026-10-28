<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\PriceCheckCore;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Services\Mapper\MapperService;
use CuyZ\Valinor\Mapper\MappingError;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class PoeLeagueRepository implements PoeLeagueRepositoryContract
{
    /**
     * @param  PoeLeagueModel  $model  Eloquent model the repository queries
     * @param  MapperService  $mapper  Maps raw model arrays into PoeLeagueDto DTOs
     */
    public function __construct(
        private PoeLeagueModel $model,
        private MapperService $mapper
    ) {}

    #[\Override]
    public function delete(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->delete();
    }

    #[\Override]
    public function truncate(): void
    {
        $this->model->newQuery()->truncate();
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function getAll(): Collection
    {
        return $this->model->newQuery()->get()->toBase()->map(
            fn ($league) => $this->mapper->mapFromArray(PoeLeagueDto::class, $league->toArray())
        );
    }

    /**
     * @return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function getAllWithoutRules(): Collection
    {
        return $this->model->newQuery()
            ->whereDoesntHave('check_rule')
            ->get()
            ->toBase()
            ->map(
                fn ($league) => $this->mapper->mapFromArray(PoeLeagueDto::class, $league->toArray())
            );
    }

    /**
     * @psalm-return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function getAllActiveByVersion(PoeVersionEnum $version): Collection
    {
        return $this->model->newQuery()
            ->where('poe_version', $version->value)
            ->where('is_active', true)
            ->get()
            ->toBase()
            ->map(fn ($league) => $this->mapper->mapFromArray(PoeLeagueDto::class, $league->toArray()));
    }

    /**
     * @psalm-return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function getAllActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->get()
            ->toBase()
            ->map(fn ($league) => $this->mapper->mapFromArray(PoeLeagueDto::class, $league->toArray()));
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function getByLeague(PoeVersionEnum $version): Collection
    {
        return $this->model->newQuery()
            ->where('poe_version', $version->value)
            ->get()
            ->toBase()
            ->map(fn ($league) => $this->mapper->mapFromArray(PoeLeagueDto::class, $league->toArray()));
    }

    /**
     * {@inheritDoc}
     * @throws MappingError
     */
    #[\Override]
    public function find(int $id): PoeLeagueDto
    {
        return $this->mapper->mapFromArray(
            PoeLeagueDto::class,
            $this->model->newQuery()->findOrFail($id)->toArray()
        );
    }

    /**
     * @throws MappingError
     */
    #[\Override]
    public function findActiveByNameVersion(string $name, PoeVersionEnum $version): PoeLeagueDto
    {
        return $this->mapper->mapFromArray(
            PoeLeagueDto::class,
            $this->model->newQuery()
            ->where([
                ['is_active', '=', true],
                ['poe_version', '=', $version->value],
                ['league_name', '=', $name],
            ])
            ->firstOrFail()
            ->toArray()
        );
    }

    /**
     * @param  array<int, array{
     *     league_name: string,
     *     poe_version: string,
     *     is_active: bool,
     * }> $paramsWithValues
     */
    #[\Override]
    public function upsert(array $paramsWithValues): void
    {
        $this->model->newQuery()->upsert(
            $paramsWithValues,
            ['league_name', 'poe_version'],
            ['is_active'],
        );
    }

    /**
     * @return Collection<int, PoeLeagueWithLastCoreDto>
     */
    #[\Override]
    public function getActiveWithLatestCore(): Collection
    {
        $coreQ = PriceCheckCore::query()
            ->select([
                'price_check_cores.poe_league_id as last_core_poe_league_id',
                DB::raw('MAX(created_at) as last_core_created_at'),
            ])
            ->groupBy('last_core_poe_league_id');

        $coreQ = PriceCheckCore::query()
            ->select([
                'price_check_cores.poe_league_id',
                'price_check_cores.created_at as last_core_created_at',
                'price_check_cores.id as last_core_id',
            ])
            ->joinSub($coreQ, 'latest_cores', function (JoinClause $join) {
                $join->on('latest_cores.last_core_poe_league_id', '=', 'price_check_cores.poe_league_id')
                    ->whereRaw(DB::raw('latest_cores.last_core_created_at = price_check_cores.created_at'));
            });

        return $this->model->newQuery()
            ->leftJoinSub($coreQ, 'latest_cores', function (JoinClause $join) {
                $join->on('poe_leagues.id', '=', 'latest_cores.poe_league_id');
            })
            ->where('is_active', true)
            ->get()
            ->toBase()
            ->map(fn ($league) => $this->mapper->mapFromArray(PoeLeagueWithLastCoreDto::class, $league->toArray()));
    }
}
