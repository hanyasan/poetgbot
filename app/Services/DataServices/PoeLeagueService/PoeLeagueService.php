<?php

namespace App\Services\DataServices\PoeLeagueService;

use App\DataTransferObjects\ParsingObjects\PoeNinjaLeague;
use App\DataTransferObjects\RepositoryObjects\PoeLeague;
use App\Enums\PoeVersionEnum;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PoeLeagueService implements PoeLeagueServiceContract
{
    public function __construct(
        private readonly PoeLeagueRepositoryContract $repository,
        private readonly PoeNinjaServiceContract $ninjaService,
    ) {}

    public function create(array $params): PoeLeague
    {
        Cache::tags([
            'poe_league_detail' . $params['league_name'] . $params['poe_version'],
            'poe_league_get',
        ])->flush();

        return $this->repository->create($params);
    }

    public function delete(int $id): bool
    {
        Cache::tags([
            'poe_league_id:' . $id,
            'poe_league_get',
            'poe_league',
        ])->flush();

        return $this->repository->delete($id);
    }

    public function find(int $id): PoeLeague
    {
        return Cache::tags([
            'poe_league_id:' . $id,
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
            'poe_league_get',
            'poe_league',
        ])->remember(
            'all',
            60 * 60,
            function () {
                return $this->repository->getAll();
            }
        );
    }

    /**
     * Get all leagues belonging to the given PoE version.
     *
     * @return Collection<PoeLeague>
     */
    public function getByVersion(PoeVersionEnum $version): Collection
    {
        return Cache::tags([
            'poe_league_get',
            'poe_league',
        ])->remember(
            'by_version:' . $version->value,
            60 * 60,
            function () use ($version) {
                return $this->repository->getByLeague($version);
            }
        );
    }

    public function updateOrCreate(array $paramsToMatch, array $params): PoeLeague
    {
        Cache::tags([
            'poe_league_get',
            'poe_league',
        ])->flush();

        return $this->repository->updateOrCreate($paramsToMatch, $params);
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
        Cache::tags([
            'poe_league_get',
            'poe_league',
        ])->flush();

        $this->repository->upsert(
            $paramsWithValues,
        );
    }

    public function upsertByNinja(PoeVersionEnum $version): void
    {
        $leagues = $this->ninjaService->getAvailableLeagues($version);

        $leagues = collect($leagues->economyLeagues)->map(static function (PoeNinjaLeague $league) use ($version) {
            return [
                'league_name' => $league->name,
                'poe_version' => $version->value,
                'is_active' => true,
            ];
        });

        $leagues = $this
            ->getByVersion($version)
            ->filter(static fn (PoeLeague $league) => ! $league->is_active)
            ->map(static fn (PoeLeague $league) => [
                'league_name' => $league->league_name,
                'poe_version' => $league->poe_version,
                'is_active' => false,
            ])
            ->intersectUsing($leagues, static fn ($la, $lb) => ($la['league_name'] !== $lb['league_name']) &&
                ($la['poe_version'] !== $lb['poe_version']))
            ->merge($leagues)
        ;

        $this->upsert($leagues);
    }

    public function truncate(): void
    {
        Cache::tags([
            'poe_league',
        ])->flush();

        $this->repository->truncate();
    }
}
