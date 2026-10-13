<?php

namespace App\Services\DataServices\PoeLeagueService;

use App\DataTransferObjects\ParsingObjects\PoeNinjaLeague;
use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use App\Services\CacheHelperService;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PoeLeagueService implements PoeLeagueServiceContract
{
    static string $leagueIdTag = 'poe_league_id';
    static string $leagueGetTag = 'poe_league_get';
    static string $leagueTag = 'poe_league';
    static string $leagueVersionTag = 'poe_league_version';

    public function __construct(
        private readonly PoeLeagueRepositoryContract $repository,
        private readonly PoeNinjaServiceContract $ninjaService,
    ) {}

    public function delete(int $id): bool
    {
        $this->flushAll();

        return $this->repository->delete($id);
    }

    public function find(int $id): PoeLeagueDto
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueIdTag, $id),
            CacheHelperService::generateCacheTag(self::$leagueTag),
        ])->remember(
            'by_id:' . $id,
            CacheHelperService::setHourTtl(),
            function () use ($id) {
                return $this->repository->find($id);
            }
        );
    }

    public function getAll(): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
            CacheHelperService::generateCacheTag(self::$leagueTag),
        ])->remember(
            'all',
            CacheHelperService::setHourTtl(),
            function () {
                return $this->repository->getAll();
            }
        );
    }

    /**
     * @return Collection<PoeLeagueDto>
     */
    public function getAllActiveByVersion(PoeVersionEnum $version): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueTag),
            CacheHelperService::generateCacheTag(self::$leagueVersionTag, $version->value),
        ])->remember(
            'all_active' . $version->value,
            CacheHelperService::setHourTtl(),
            function () use ($version) {
                return $this->repository->getAllActiveByVersion($version);
            }
        );
    }

    /**
     * @return Collection<PoeLeagueDto>
     */
    public function getAllActive(): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueTag),
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
        ])->remember(
            'all_active_get',
            CacheHelperService::setHourTtl(),
            function () {
                return $this->repository->getAllActive();
            }
        );
    }

    /**
     * Get all leagues belonging to the given PoE version.
     *
     * @return Collection<PoeLeagueDto>
     */
    public function getByVersion(PoeVersionEnum $version): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueTag),
            CacheHelperService::generateCacheTag(self::$leagueVersionTag, $version->value),
        ])->remember(
            'by_version:' . $version->value,
            CacheHelperService::setHourTtl(),
            function () use ($version) {
                return $this->repository->getByLeague($version);
            }
        );
    }

    public function upsertByNinja(PoeVersionEnum $version): void
    {
        $this->flushVersion($version);

        $leagues = $this->ninjaService->getAvailableLeagues($version);

        $leagues = collect($leagues->economyLeagues)->map(static function (PoeNinjaLeague $league) use ($version) {
            return [
                'league_name' => $league->name,
                'poe_version' => $version->value,
                'is_active' => true,
            ];
        });

        $ninjaNames = $leagues->pluck('league_name');

        // Active leagues that poe.ninja no longer lists become inactive.
        $leagues = $this
            ->getByVersion($version)
            ->filter(static fn (PoeLeagueDto $league) => $league->is_active)
            ->reject(static fn (PoeLeagueDto $league) => $ninjaNames->contains($league->league_name))
            ->map(static fn (PoeLeagueDto $league) => [
                'league_name' => $league->league_name,
                'poe_version' => $league->poe_version,
                'is_active' => false,
            ])
            ->merge($leagues)
        ;

        $this->repository->upsert($leagues->values()->all());
    }

    public function truncate(): void
    {
        $this->flushAll();

        $this->repository->truncate();
    }

    public function flushAll(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueTag),
        ])->flush();
    }

    public function flushGet(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
        ])->flush();
    }

    public function flushId(int $id): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
            CacheHelperService::generateCacheTag(self::$leagueIdTag, $id),
        ])->flush();
    }

    public function flushVersion(PoeVersionEnum $version): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
            CacheHelperService::generateCacheTag(self::$leagueVersionTag, $version->value),
        ])->flush();
    }
}
