<?php

namespace App\Services\DataServices\Currency\CurrencyTypeService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\CurrencyType\CurrencyTypeRepositoryContract;
use App\Services\CacheHelperService;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final readonly class CurrencyTypeService implements CurrencyTypeServiceContract
{

    public function __construct(
        private CurrencyTypeRepositoryContract $repository,
        private PoeNinjaServiceContract        $ninjaService,
        private PoeLeagueServiceContract       $leagueService,
    ) {}

    #[\Override]
    public function delete(int $id): bool
    {
        $this->flushId($id);

        return $this->repository->delete($id);
    }

    /**
     * @return Collection<int, CurrencyTypeWithLeagueDto>
     */
    #[\Override]
    public function getAll(): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_GET_TAG),
        ])->remember(
            'all',
            CacheHelperService::setHourTtl(),
            function () {
                return $this->repository->getAll();
            }
        );
    }

    /**
     * @psalm-return Collection<int, CurrencyTypeDto>
     */
    #[\Override]
    public function getAllByLeague(int $leagueId): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_GET_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_BY_LEAGUE_TAG, $leagueId),
        ])->remember(
            'all_by_league' . $leagueId,
            CacheHelperService::setHourTtl(),
            function () use ($leagueId) {
                return $this->repository->getAllByLeague($leagueId);
            }
        );
    }

    #[\Override]
    public function freshAllByNinja(): void
    {
        $this->flushGet();

        $this->leagueService
            ->getAllActive()
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
    #[\Override]
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
     * @return Collection<int, CurrencyTypeWithPricesDto>
     */
    #[\Override]
    public function getAllByLeagueWithPriceByCore(int $leagueId, int $coreId): Collection
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_GET_TAG),
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_BY_LEAGUE_TAG, $leagueId),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_ID_TAG, $coreId),
            CacheHelperService::generateCacheTag(CacheHelperService::CORE_BY_LEAGUE_TAG, $leagueId),
        ])->remember(
            'all_by_league_core' . $leagueId . '_' . $coreId,
            CacheHelperService::setHourTtl(),
            function () use ($leagueId, $coreId) {
                return $this->repository->getAllByLeagueWithPriceByCore($leagueId, $coreId);
            }
        );
    }

    #[\Override]
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

    #[\Override]
    public function flushAll(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_TAG),
        ])->flush();
    }

    #[\Override]
    public function flushId(int $id): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_ID_TAG, $id),
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_GET_TAG),
        ])->flush();
    }

    #[\Override]
    public function flushLeagueId(int $id): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_BY_LEAGUE_TAG, $id),
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_GET_TAG),
        ])->flush();
    }

    #[\Override]
    public function flushGet(): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(CacheHelperService::CURRENCY_TYPE_GET_TAG),
        ])->flush();
    }
}
