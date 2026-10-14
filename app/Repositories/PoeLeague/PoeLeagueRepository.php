<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Models\LeagueCheckRule;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Support\Collection;

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
     * @psalm-return Collection<int, object>
     */
    #[\Override]
    public function getAll(): Collection
    {
        return $this->getQuery()->get()->toBase()->map(
            fn ($league) => $this->mapper->mapFromArray(PoeLeagueDto::class, $league->toArray())
        );
    }

    /**
     * @psalm-return Collection<int, object>
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
     * @psalm-return Collection<int, object>
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
     * @psalm-return Collection<int, object>
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
}
