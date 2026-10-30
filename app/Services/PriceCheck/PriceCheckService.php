<?php

namespace App\Services\PriceCheck;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeLine;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\DataTransferObjects\ServiceObjects\LeaguePricesDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\DataServices\PoeLeague\PoeLeagueServiceContract;
use App\Services\PoeNinja\PoeNinjaServiceContract;
use App\Utility\MapperService;
use Carbon\CarbonImmutable;
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
        private PoeLeagueServiceContract  $poeLeagueService,
        private MapperService $mapperService,
    ) {}

    /**
     * @psalm-return Collection<int, LeaguePricesDto>|Collection<never, never>
     */
    #[\Override]
    public function importPickedLeaguesPrices(): Collection
    {
        $leagues = $this->pickLeaguesForCheck();
        $prices = collect();

        Log::info('Start import picked leagues prices');

        foreach ($leagues as $league) {
            try {
                /** @var Collection<int, LeaguePricesDto> $prices */
                $prices->add($this->importLeagueLatestPrices($league));
            } catch (\Throwable $exception) {
                Log::error($exception->getMessage());
            }
        }

        Log::info('Finish import picked leagues prices, ' . count($prices) . ' leagues imported');

        return $prices;
    }

    /**
     * @throws \Throwable
     */
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
     * @return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function pickLeaguesForCheck(): Collection
    {
        return $this->poeLeagueService->getActiveWithLatestCore()
            ->filter(function (PoeLeagueWithLastCoreDto $poeLeagueDto) {
                $minutesCheck = $poeLeagueDto->check_rule?->minutes_check;

                // No rule (or a null interval) means the league is not scheduled for checks.
                if ($minutesCheck === null) {
                    return false;
                }

                return is_null($poeLeagueDto->last_core_created_at) || CarbonImmutable::now()->greaterThanOrEqualTo(
                    CarbonImmutable::parse($poeLeagueDto->last_core_created_at)->addMinutes($minutesCheck)
                );
            })
            ->map(fn (PoeLeagueWithLastCoreDto $league) => $this->mapperService->mapFromArray(PoeLeagueDto::class, get_object_vars($league)))
            ;
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
        $prices = [];

        foreach ($currencyTypes as $currencyType) {
            /** @var PoeNinjaExchangeLine|null $tradeItem */
            $tradeItem = $items->where('id', $currencyType->currency_trade_id)->first();

            if (! $tradeItem || is_null($tradeItem->primaryValue)) continue;

            $prices[] = [
                'rate' => $tradeItem->primaryValue,
                'price_check_core_id' => $coreId,
                'currency_type_id' => $currencyType->id,
            ];
        }

        return $prices;
    }
}
