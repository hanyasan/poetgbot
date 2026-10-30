<?php

namespace App\Services\DataServices\Currency\PriceCheckCoreService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\PriceCheckCore\PriceCheckCoreRepositoryContract;
use App\Services\DataServices\PoeLeague\PoeLeagueServiceContract;
use App\Services\PoeNinja\PoeNinjaServiceContract;
use App\Utility\CacheHelperService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final readonly class PriceCheckCoreService implements PriceCheckCoreServiceContract
{

    public function __construct(
        private PriceCheckCoreRepositoryContract $repository,
        private PoeNinjaServiceContract          $ninjaService,
        private PoeLeagueServiceContract         $leagueService,
    ) {}

    #[\Override]
    public function find(int $id): PriceCheckCoreDto
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_ID_TAG, $id),
        ])->remember(
            'by_id:' . $id,
            CacheHelperService::setHourTtl(),
            function () use ($id) {
                return $this->repository->find($id);
            }
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getAll(): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_GET_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_TAG),
        ])->remember(
            'all',
            CacheHelperService::setHourTtl(),
            function () {
                return $this->repository->getAll();
            }
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getByLeague(int $leagueId): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_BY_LEAGUE_TAG, $leagueId),
        ])->remember(
            'by_league:' . $leagueId,
            CacheHelperService::setHourTtl(),
            function () use ($leagueId) {
                return $this->repository->getByLeague($leagueId);
            }
        );
    }

    #[\Override]
    public function getLatestByLeague(int $leagueId): PriceCheckCoreDto
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_BY_LEAGUE_TAG, $leagueId),
        ])->remember(
            'latest_by_league:' . $leagueId,
            CacheHelperService::setHourTtl(),
            function () use ($leagueId) {
                return $this->repository->getLatestByLeague($leagueId);
            }
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getFromDateByLeague(Carbon $dateFrom, int $leagueId): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_BY_LEAGUE_TAG, $leagueId),
        ])->remember(
            'from_date_by_league:' . $leagueId . ':' . $dateFrom->toIso8601String(),
            CacheHelperService::setHourTtl(),
            function () use ($dateFrom, $leagueId) {
                return $this->repository->getFromDateByLeague($dateFrom, $leagueId);
            }
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function create(array $coreData, int $leagueId): PriceCheckCoreDto
    {
        $this->flushLeagueId($leagueId);

        return $this->repository->createCore($leagueId, $coreData['types']);
    }

    #[\Override]
    public function createNewByNinja(int $leagueId): PriceCheckCoreDto
    {
        $league = $this->leagueService->find($leagueId);

        $prices = $this->ninjaService->getCurrencyPrices(
            PoeVersionEnum::from($league->poe_version),
            $league->league_name
        );

        return $this->create(['types' => $this->prepTypes($prices)], $leagueId);
    }

    /**
     * @param PoeNinjaExchangeResponse $prices
     *
     * @return array<int, array{
     *     is_primary: bool,
     *     rate: float,
     *     type_id: string,
     *     name: string,
     * }>
     */
    #[\Override]
    public function prepTypes(PoeNinjaExchangeResponse $prices): array
    {
        return array_map(static function (PoeNinjaExchangeItem $item) use ($prices) {
            $isPrimary = $prices->core->primary === $item->id;

            return [
                'is_primary' => $isPrimary,
                'rate' => $isPrimary ? 1 : $prices->core->rates[$item->id],
                'type_id' => $item->id,
                'name' => $item->name,
            ];
        }, $prices->core->items);
    }

    #[\Override]
    public function flushAll(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_TAG),
        ])->flush();
    }

    #[\Override]
    public function flushLeagueId(int $leagueId): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_GET_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_BY_LEAGUE_TAG, $leagueId),
        ])->flush();
    }
}
