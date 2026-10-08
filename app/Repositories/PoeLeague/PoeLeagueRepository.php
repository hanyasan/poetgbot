<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\Repository\PoeLeague;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\PoeLeague as PoeLeagueModel;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Support\Collection;

final class PoeLeagueRepository extends BaseEloquentRepository implements PoeLeagueRepositoryContract
{
    public function __construct(
        PoeLeagueModel $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    public function getAll(): Collection
    {
        return $this->getQuery()->get()->map(
            fn ($league) => $this->mapper->mapFromArray(PoeLeague::class, $league->toArray())
        );
    }

    public function getByVersion(PoeVersionEnum $version): Collection
    {
        return $this->getQuery()
            ->where('poe_version', $version->value)
            ->get()
            ->map(fn ($league) => $this->mapper->mapFromArray(PoeLeague::class, $league->toArray()));
    }

    public function find(int $id): PoeLeague
    {
        return $this->mapper->mapFromArray(
            PoeLeague::class,
            parent::find($id)->toArray()
        );
    }

    public function create(array $params): PoeLeague
    {
        return $this->mapper->mapFromArray(
            PoeLeague::class,
            parent::create($params)->toArray()
        );
    }

    public function updateOrCreate(array $paramsToMatch, array $params): PoeLeague
    {
        return $this->mapper->mapFromArray(
            PoeLeague::class,
            parent::updateOrCreate($paramsToMatch, $params)->toArray()
        );
    }
}
