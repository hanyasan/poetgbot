<?php

namespace App\Services\DataServices\PoeLeague;

use App\DataTransferObjects\ParsingObjects\PoeNinjaLeague;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\PoeLeague\LeagueRuleRepositoryContract;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use App\Services\PoeNinja\PoeNinjaServiceContract;
use App\Utility\CacheHelperService;
use CuyZ\Valinor\Mapper\MappingError;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final readonly class PoeLeagueService implements PoeLeagueServiceContract
{

    public function __construct(
        private PoeLeagueRepositoryContract  $repository,
        private PoeNinjaServiceContract      $ninjaService,
        private LeagueRuleRepositoryContract $leagueRuleRepository,
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
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_ID_TAG, $id),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_GET_TAG),
        ])->remember(
            'by_id:' . $id,
            CacheHelperService::setHourTtl(),
            function () use ($id) {
                return $this->repository->find($id);
            }
        );
    }

    #[\Override]
    public function findActiveByNameVersion(string $name, PoeVersionEnum $version): PoeLeagueDto
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_VERSION_TAG, $version->value),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_NAME_TAG, $name),
        ])->remember(
            'by_name_version:' . $name . ' ' . $version->value,
            CacheHelperService::setHourTtl(),
            function () use ($name, $version) {
                return $this->repository->findActiveByNameVersion($name, $version);
            }
        );
    }

    #[\Override]
    public function getAll(): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_GET_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_TAG),
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
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_VERSION_TAG, $version->value),
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
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_GET_TAG),
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
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_VERSION_TAG, $version->value),
        ])->remember(
            'by_version:' . $version->value,
            CacheHelperService::setHourTtl(),
            function () use ($version) {
                return $this->repository->getByLeague($version);
            }
        );
    }

    /**
     * @throws GuzzleException
     * @throws MappingError
     */
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

        $this->leagueRuleRepository->upsertDefaultForLeagues(
            $this->repository->getAllWithoutRules()->pluck('id')->toArray()
        );
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
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_TAG),
        ])->flush();
    }

    #[\Override]
    public function flushGet(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_GET_TAG),
        ])->flush();
    }

    #[\Override]
    public function flushId(int $id): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_GET_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_ID_TAG, $id),
        ])->flush();
    }

    #[\Override]
    public function flushVersion(PoeVersionEnum $version): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_GET_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_VERSION_TAG, $version->value),
        ])->flush();
    }

    #[\Override]
    public function updateOrCreateRule(int|null $minutesToCheck, int $leagueId): void
    {
        $this->find($leagueId);

        $this->flushId($leagueId);

        $this->leagueRuleRepository->updateOrCreate($minutesToCheck, $leagueId);
    }

    /**
     * @return Collection<int, PoeLeagueWithLastCoreDto>
     */
    #[\Override]
    public function getActiveWithLatestCore(): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::LEAGUE_GET_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_GET_TAG),
        ])->remember(
            'with_latest_core',
            CacheHelperService::setHourTtl(),
            function () {
                return $this->repository->getActiveWithLatestCore();
            }
        );
    }
}
