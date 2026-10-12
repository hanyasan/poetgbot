<?php

namespace App\Services\DataServices\Currency\CurrencyTypeService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\CurrencyType\CurrencyTypeRepositoryContract;
use App\Services\CacheHelperService;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CurrencyTypeService implements CurrencyTypeServiceContract
{
    static string $currencyTypeIdTad = 'currency_type_id';
    static string $currencyTypeGetTag = 'currency_type_get';
    static string $currencyTypeTag = 'currency_type';
    static string $currencyTypeByLeagueTag = 'currency_type_by_league';
    static string $currencyTypeByCoreTag = 'currency_type_by_core';

    public function __construct(
        private readonly CurrencyTypeRepositoryContract $repository,
        private readonly PoeNinjaServiceContract $ninjaService,
        private readonly PoeLeagueServiceContract $leagueService,
    ) {}

    public function delete(int $id): bool
    {
        $this->flushId($id);

        return $this->repository->delete($id);
    }

    /**
     * @return Collection<CurrencyTypeWithLeagueDto>
     */
    public function getAll(): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeTag),
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
        ])->remember(
            'all',
            CacheHelperService::setHourTtl(),
            function () {
                return $this->repository->getAll();
            }
        );
    }

    /**
     * @return Collection<CurrencyTypeWithLeagueDto>
     */
    public function getAllByLeague(int $leagueId): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeTag),
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
            CacheHelperService::generateCacheTag(self::$currencyTypeByLeagueTag, $leagueId),
        ])->remember(
            'all_by_league' . $leagueId,
            CacheHelperService::setHourTtl(),
            function () use ($leagueId) {
                return $this->repository->getAllByLeague($leagueId);
            }
        );
    }

    public function freshAllByNinja(): void
    {
        $this->flushGet();

        $this->leagueService->getAllActive(PoeVersionEnum::POE_1)
            ->merge($this->leagueService->getAllActive(PoeVersionEnum::POE_2))
            ->each(fn (PoeLeagueDto $league) => $this->upsertByNinja($league->id))
        ;
    }

    /**
     * @param array<int, array{
     *     currency_type_name: string,
     *     currency_trade_id: string,
     *     currency_ninja_details_id: string,
     * }> $typeData
     * @param int $leagueId
     *
     * @return void
     */
    public function upsert(array $typeData, int $leagueId): void
    {
        $this->flushLeagueId($leagueId);

        $insertData = collect($typeData)
            ->map(fn (array $currencyItem) => [
                'currency_type_name' => $currencyItem['currency_type_name'],
                'currency_trade_id' => $currencyItem['currency_trade_id'],
                'currency_ninja_details_id' => $currencyItem['currency_ninja_details_id'],
                'poe_league_id' => $leagueId,
            ])
            ->toArray()
        ;

        $this->repository->upsert($insertData);
    }

    /**
     * @return Collection<CurrencyTypeWithPricesDto>
     */
    public function getAllByLeagueWithPriceByCore(int $leagueId, int $coreId): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeTag),
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
            CacheHelperService::generateCacheTag(self::$currencyTypeByLeagueTag, $leagueId),
            CacheHelperService::generateCacheTag(self::$currencyTypeByCoreTag, $coreId),
        ])->remember(
            'all_by_league_core' . $leagueId . '_' . $coreId,
            CacheHelperService::setHourTtl(),
            function () use ($leagueId, $coreId) {
                return $this->repository->getAllByLeagueWithPriceByCore($leagueId, $coreId);
            }
        );
    }

    public function upsertByNinja(int $leagueId): void
    {
        $this->flushLeagueId($leagueId);

        $league = $this->leagueService->find($leagueId);

        $prices = $this->ninjaService->getCurrencyPrices(
            PoeVersionEnum::from($league->poe_version),
            $league->league_name
        );

        $insertData = collect($prices->items)
            ->map(fn (PoeNinjaExchangeItem $currencyType) => [
                'currency_type_name' => $currencyType->name,
                'currency_trade_id' => $currencyType->id,
                'currency_ninja_details_id' => $currencyType->detailsId,
                'poe_league_id' => $league->id,
            ])
            ->toArray()
        ;

        $this->repository->upsert($insertData);
    }

    public function flushAll(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeTag),
        ])->flush();
    }

    public function flushId(int $id): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeIdTad, $id),
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
        ])->flush();
    }

    public function flushLeagueId(int $id): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeByLeagueTag, $id),
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
        ])->flush();
    }

    public function flushGet(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
        ])->flush();
    }
}
