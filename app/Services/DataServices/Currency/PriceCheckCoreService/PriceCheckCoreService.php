<?php

namespace App\Services\DataServices\Currency\PriceCheckCoreService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\PriceCheckCore\PriceCheckCoreRepositoryContract;
use App\Services\CacheHelperService;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PriceCheckCoreService implements PriceCheckCoreServiceContract
{
    static string $coreTag = 'price_check_core';
    static string $coreGetTag = 'price_check_core_get';
    static string $coreIdTag = 'price_check_core_id';
    static string $coreByLeagueTag = 'price_check_core_by_league';

    public function __construct(
        private readonly PriceCheckCoreRepositoryContract $repository,
        private readonly PoeNinjaServiceContract $ninjaService,
        private readonly PoeLeagueServiceContract $leagueService,
    ) {}

    #[\Override]
    public function find(int $id): PriceCheckCoreDto
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$coreTag),
            CacheHelperService::generateCacheTag(self::$coreIdTag, $id),
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
            CacheHelperService::generateCacheTag(self::$coreGetTag),
            CacheHelperService::generateCacheTag(self::$coreTag),
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
            CacheHelperService::generateCacheTag(self::$coreTag),
            CacheHelperService::generateCacheTag(self::$coreByLeagueTag, $leagueId),
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
            CacheHelperService::generateCacheTag(self::$coreTag),
            CacheHelperService::generateCacheTag(self::$coreByLeagueTag, $leagueId),
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
            CacheHelperService::generateCacheTag(self::$coreTag),
            CacheHelperService::generateCacheTag(self::$coreByLeagueTag, $leagueId),
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
     * @return (bool|float|int|string)[][]
     *
     * @psalm-return array<array{is_primary: bool, rate: 1|float, type_id: string, name: string}>
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
            CacheHelperService::generateCacheTag(self::$coreTag),
        ])->flush();
    }

    #[\Override]
    public function flushLeagueId(int $leagueId): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$coreByLeagueTag, $leagueId),
        ])->flush();
    }
}
