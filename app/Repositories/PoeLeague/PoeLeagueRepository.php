<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\PriceCheckCore;
use App\Models\LeagueCheckRule;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PoeLeagueRepository extends BaseEloquentRepository implements PoeLeagueRepositoryContract
{
    /**
     * @param  PoeLeagueModel  $model  Eloquent model the repository queries
     * @param  MapperService  $mapper  Maps raw model arrays into PoeLeagueDto DTOs
     */
    public function __construct(
        PoeLeagueModel $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function getAll(): Collection
    {
        return $this->getQuery()->get()->toBase()->map(
            fn ($league) => $this->mapper->mapFromArray(PoeLeagueDto::class, $league->toArray())
        );
    }

    /**
     * @psalm-return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function getAllActiveByVersion(PoeVersionEnum $version): Collection
    {
        return $this->getQuery()
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
        return $this->getQuery()
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
        return $this->getQuery()
            ->where('poe_version', $version->value)
            ->get()
            ->toBase()
            ->map(fn ($league) => $this->mapper->mapFromArray(PoeLeagueDto::class, $league->toArray()));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function find(int $id): PoeLeagueDto
    {
        return $this->mapper->mapFromArray(
            PoeLeagueDto::class,
            parent::find($id)->toArray()
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
        parent::getQuery()->upsert(
            $paramsWithValues,
            ['league_name', 'poe_version'],
            ['is_active'],
        );
    }

    #[\Override]
    public function updateOrCreateRule(int|null $minutesToCheck, int $leagueId): void
    {
        LeagueCheckRule::query()
            ->updateOrCreate(
                ['poe_league_id' => $leagueId],
                [
                    'minutes_check' => $minutesToCheck,
                    'poe_league_id' => $leagueId,
                ]
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

        return $this->getQuery()
            ->leftJoinSub($coreQ, 'latest_cores', function (JoinClause $join) {
                $join->on('poe_leagues.id', '=', 'latest_cores.poe_league_id');
            })
            ->where('is_active', true)
            ->get()
            ->toBase()
            ->map(fn ($league) => $this->mapper->mapFromArray(PoeLeagueWithLastCoreDto::class, $league->toArray()));
    }
}
