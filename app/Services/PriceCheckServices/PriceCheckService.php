<?php

namespace App\Services\PriceCheckServices;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeLine;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\DataTransferObjects\ServiceObjects\LeaguePricesDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class PriceCheckService implements PriceCheckServiceContract
{
    public function __construct(
        private PoeLeagueServiceContract $poeLeagueService,
        private CurrencyTypeServiceContract  $currencyTypeService,
        private PriceCheckCoreServiceContract $priceCheckCoreService,
        private PoeNinjaServiceContract $poeNinjaService,
        private CurrencyPriceRepositoryContract $currencyPriceRepository,
    ) {}

    public function importLeagueLatestPrices(int $leagueId): LeaguePricesDto
    {
        DB::beginTransaction();

        try {
            $league = $this->poeLeagueService->find($leagueId);

            $exchangeResponse = $this->poeNinjaService->getCurrencyPrices(
                PoeVersionEnum::from($league->poe_version),
                $league->league_name,
            );

            $coreDto = $this->priceCheckCoreService->create(
                [
                    'types' => $this->priceCheckCoreService->prepTypes($exchangeResponse)
                ],
                $leagueId,
            );

            $this->currencyTypeService->upsert(
                array_map(fn (PoeNinjaExchangeItem $item) => [
                    'currency_type_name' => $item->name,
                    'currency_trade_id' => $item->id,
                    'currency_ninja_details_id' => $item->detailsId,
                ], $exchangeResponse->items),
                $leagueId
            );

            $currencyTypes = $this->currencyTypeService->getAllByLeague($leagueId);

            $this->currencyPriceRepository->insert($this->prepPrices(
                $currencyTypes,
                $coreDto->id,
                $exchangeResponse->lines,
            ));
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        DB::commit();

        return new LeaguePricesDto(
            $league,
            $coreDto,
            $this->currencyTypeService->getAllByLeagueWithPriceByCore($leagueId, $coreDto->id)->toArray(),
        );
    }

    /**
     * @param Collection<CurrencyTypeDto> $currencyTypes
     * @param array<int, PoeNinjaExchangeLine> $items
     *
     * @return array<int, array{
     *     rate: float,
     *     price_check_core_id: int,
     *     currency_type_id: int,
     * }>
     */
    private function prepPrices(
        Collection $currencyTypes,
        int $coreId,
        array $items,
    ): array {
        $items = collect($items);

        return $currencyTypes->map(static function (CurrencyTypeDto $currencyType) use ($items, $coreId) {
            return [
                'rate' => $items->where('id', $currencyType->currency_trade_id)->first()->primaryValue,
                'price_check_core_id' => $coreId,
                'currency_type_id' => $currencyType->id,
            ];
        })->toArray();
    }
}
