<?php

namespace App\Services\PriceCheckServices\PriceCheckService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeLine;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\DataTransferObjects\ServiceObjects\LeaguePricesDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use App\Services\PriceCheckServices\LeagueCheckPickerService\LeagueCheckPickerServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class PriceCheckService implements PriceCheckServiceContract
{
    public function __construct(
        private CurrencyTypeServiceContract  $currencyTypeService,
        private PriceCheckCoreServiceContract $priceCheckCoreService,
        private PoeNinjaServiceContract $poeNinjaService,
        private CurrencyPriceRepositoryContract $currencyPriceRepository,
        private LeagueCheckPickerServiceContract  $leagueCheckPickerService,
    ) {}

    /**
     * @psalm-return Collection<int, never>|Collection<never, never>
     */
    #[\Override]
    public function importPickedLeaguesPrices(): Collection
    {
        $leagues = $this->leagueCheckPickerService->pickLeaguesForCheck();
        $prices = collect();

        Log::info('Start import picked leagues prices');

        foreach ($leagues as $league) {
            try {
                $prices->add($this->importLeagueLatestPrices($league));
            } catch (\Throwable $exception) {
                Log::error($exception->getMessage());
            }
        }

        Log::info('Finish import picked leagues prices, ' . count($prices) . ' leagues imported');

        return $prices;
    }

    #[\Override]
    public function importLeagueLatestPrices(PoeLeagueDto $league): LeaguePricesDto
    {
        DB::beginTransaction();

        Log::info('Importing ' .
            $league->league_name .
            ' ' .
            $league->poe_version .
            ' league prices'
        );

        try {
            $exchangeResponse = $this->poeNinjaService->getCurrencyPrices(
                PoeVersionEnum::from($league->poe_version),
                $league->league_name,
            );

            $coreDto = $this->priceCheckCoreService->create(
                [
                    'types' => $this->priceCheckCoreService->prepTypes($exchangeResponse)
                ],
                $league->id,
            );

            $this->currencyTypeService->upsert(
                array_map(fn (PoeNinjaExchangeItem $item) => [
                    'currency_type_name' => $item->name,
                    'currency_trade_id' => $item->id,
                    'currency_ninja_details_id' => $item->detailsId,
                ], $exchangeResponse->items),
                $league->id
            );

            $currencyTypes = $this->currencyTypeService->getAllByLeague($league->id);

            $this->currencyPriceRepository->insert($this->prepPrices(
                $currencyTypes,
                $coreDto->id,
                $exchangeResponse->lines,
            ));
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error($league->league_name . ' league prices import failed');

            throw $exception;
        }

        DB::commit();

        $prices = new LeaguePricesDto(
            $league,
            $coreDto,
            $this->currencyTypeService->getAllByLeagueWithPriceByCore($league->id, $coreDto->id)->toArray(),
        );

        Log::info($league->league_name . ' league prices imported');

        return $prices;
    }

    /**
     * @param Collection<int, CurrencyTypeDto> $currencyTypes
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
        $prices = collect();

        foreach ($currencyTypes as $currencyType) {
            /** @var PoeNinjaExchangeLine $tradeItem */
            $tradeItem = $items->where('id', $currencyType->currency_trade_id)->first();

            if (! $tradeItem || ! $tradeItem->primaryValue) continue;

            $prices->add([
                'rate' => $tradeItem->primaryValue,
                'price_check_core_id' => $coreId,
                'currency_type_id' => $currencyType->id,
            ]);
        }

        return $prices->toArray();
    }
}
