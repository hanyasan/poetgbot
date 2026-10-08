<?php

namespace App\Services\DataServices\PoeVersionService;

use App\DataTransferObjects\Repository\PoeLeague;
use App\Enums\PoeVersionEnum;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PoeVersionService implements PoeVersionServiceContract
{
    public function __construct(
        private readonly PoeLeagueRepositoryContract $repository
    ) {}

    public function create(array $params): PoeLeague
    {
        Cache::tags([
            'poe_league_get',
        ])->flush();

        return $this->repository->create($params);
    }

    public function delete(int $id): bool
    {
        Cache::tags([
            'poe_league_get',
        ])->flush();

        return $this->repository->delete($id);
    }

    public function find(int $id): PoeLeague
    {
        return Cache::tags([
            'poe_league',
        ])->remember(
            'by_id:' . $id,
            60 * 60,
            function () use ($id) {
                return $this->repository->find($id);
            }
        );
    }

    public function getAll(): Collection
    {
        return Cache::tags([
            'poe_league',
            'poe_league_get',
        ])->remember(
            'all',
            60 * 60,
            function () {
                return $this->repository->getAll();
            }
        );
    }

    public function getByVersion(PoeVersionEnum $version): Collection
    {
        return Cache::tags([
            'poe_league',
            'poe_league_get',
        ])->remember(
            'by_version:' . $version->value,
            60 * 60,
            function () use ($version) {
                return $this->repository->getByVersion($version);
            }
        );
    }

    public function updateOrCreate(array $paramsToMatch, array $params): PoeLeague
    {
        Cache::tags([
            'poe_league',
        ])->flush();

        return $this->repository->updateOrCreate($paramsToMatch, $params);
    }

    public function truncate(): void
    {
        Cache::tags([
            'poe_league',
        ])->flush();

        $this->repository->truncate();
    }
}
