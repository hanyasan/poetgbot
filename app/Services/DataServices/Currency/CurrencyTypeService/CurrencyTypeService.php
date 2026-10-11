<?php

namespace App\Services\DataServices\Currency\CurrencyTypeService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
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
    static string $currencyTypeDetailTag = 'currency_type_detail';
    static string $currencyTypeGetTag = 'currency_type_get';
    static string $currencyTypeTag = 'currency_type';
    static string $currencyTypeByLeagueTag = 'currency_type_by_league';

    public function __construct(
        private readonly CurrencyTypeRepositoryContract $repository,
        private readonly PoeNinjaServiceContract $ninjaService,
        private readonly PoeLeagueServiceContract $leagueService,
    ) {}

    public function delete(int $id): bool
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeIdTad, $id),
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
        ])->flush();

        return $this->repository->delete($id);
    }

    public function findByDetail(string $detailId): CurrencyTypeWithLeagueDto
    {
        return Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeDetailTag, $detailId),
            CacheHelperService::generateCacheTag(self::$currencyTypeTag),
        ])->remember(
            'by_detail:' . $detailId,
            CacheHelperService::setHourTtl(),
            function () use ($detailId) {
                return $this->repository->findByDetailId($detailId);
            }
        );
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
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeTag),
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
        ])->flush();

        $this->leagueService->getAllActive(PoeVersionEnum::POE_1)
            ->merge($this->leagueService->getAllActive(PoeVersionEnum::POE_2))
            ->each(fn (PoeLeagueDto $league) => $this->upsertByNinja($league->id))
        ;
    }

    public function upsertByNinja(int $leagueId): void
    {
        Cache::tags([
            CacheHelperService::generateCacheTag(self::$currencyTypeTag),
            CacheHelperService::generateCacheTag(self::$currencyTypeGetTag),
            CacheHelperService::generateCacheTag(self::$currencyTypeByLeagueTag, $leagueId),
        ])->flush();

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
}
