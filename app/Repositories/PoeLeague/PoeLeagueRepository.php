<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\RepositoryObjects\PoeLeague;
use App\Enums\PoeVersionEnum;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Support\Collection;

final class PoeLeagueRepository extends BaseEloquentRepository implements PoeLeagueRepositoryContract
{
    /**
     * @param  PoeLeagueModel  $model  Eloquent model the repository queries
     * @param  MapperService  $mapper  Maps raw model arrays into PoeLeague DTOs
     */
    public function __construct(
        PoeLeagueModel $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): Collection
    {
        return $this->getQuery()->get()->toBase()->map(
            fn ($league) => $this->mapper->mapFromArray(PoeLeague::class, $league->toArray())
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getByLeague(PoeVersionEnum $version): Collection
    {
        return $this->getQuery()
            ->where('poe_version', $version->value)
            ->get()
            ->toBase()
            ->map(fn ($league) => $this->mapper->mapFromArray(PoeLeague::class, $league->toArray()));
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id): PoeLeague
    {
        return $this->mapper->mapFromArray(
            PoeLeague::class,
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
    public function upsert(array $paramsWithValues): void
    {
        parent::getQuery()->upsert(
            $paramsWithValues,
            ['league_name', 'poe_version'],
            ['is_active'],
        );
    }
}
