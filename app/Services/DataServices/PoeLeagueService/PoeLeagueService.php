<?php

namespace App\Services\DataServices\PoeLeagueService;

use App\DataTransferObjects\ParsingObjects\PoeNinjaLeague;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use App\Services\CacheHelperService;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreService;
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

    #[\Override]
    public function delete(int $id): bool
    {
        $this->flushAll();

        return $this->repository->delete($id);
    }

    /**
     * @param int $id
     * @return PoeLeagueDto
     *
     */
    #[\Override]
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

    #[\Override]
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
     * @return Collection<int, PoeLeagueDto>
     */
    #[\Override]
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
     * @return Collection<int, PoeLeagueDto>
     */
    #[\Override]
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
     * @return Collection<int, PoeLeagueDto>
     */
    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function truncate(): void
    {
        $this->flushAll();

        $this->repository->truncate();
    }

    #[\Override]
    public function flushAll(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueTag),
        ])->flush();
    }

    #[\Override]
    public function flushGet(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
        ])->flush();
    }

    #[\Override]
    public function flushId(int $id): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
            CacheHelperService::generateCacheTag(self::$leagueIdTag, $id),
        ])->flush();
    }

    #[\Override]
    public function flushVersion(PoeVersionEnum $version): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
            CacheHelperService::generateCacheTag(self::$leagueVersionTag, $version->value),
        ])->flush();
    }

    #[\Override]
    public function updateOrCreateRule(int|null $minutesToCheck, int $leagueId): void
    {
        $this->find($leagueId);

        $this->flushId($leagueId);

        $this->repository->updateOrCreateRule($minutesToCheck, $leagueId);
    }

    /**
     * @return Collection<int, PoeLeagueWithLastCoreDto>
     */
    #[\Override]
    public function getActiveWithLatestCore(): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$leagueTag),
            CacheHelperService::generateCacheTag(self::$leagueGetTag),
            CacheHelperService::generateCacheTag(PriceCheckCoreService::$coreTag),
            CacheHelperService::generateCacheTag(PriceCheckCoreService::$coreGetTag),
        ])->remember(
            'with_latest_core',
            CacheHelperService::setHourTtl(),
            function () {
                return $this->repository->getActiveWithLatestCore();
            }
        );
    }
}
