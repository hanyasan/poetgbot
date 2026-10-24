<?php

namespace App\Services\DataServices\Currency\CurrencyCompactService;

use App\Repositories\Currency\Compact\CurrencyCompactRepositoryContract;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Repositories\Currency\PriceCheckCore\PriceCheckCoreRepositoryContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class CurrencyCompactService implements CurrencyCompactServiceContract
{
    public function __construct(
        private PriceCheckCoreRepositoryContract $priceCheckCoreRepository,
        private CurrencyPriceRepositoryContract $currencyPriceRepository,
        private CurrencyCompactRepositoryContract $currencyCompactRepository,
        private PriceCheckCoreServiceContract  $priceCheckCoreService,
    ) {
    }

    #[\Override]
    public function compactCoreData(): void
    {
        $compatToDate = Carbon::now('UTC')->subDays(config('poe.compact_price.days_fresh'));

        $cores = collect($this->priceCheckCoreRepository->getLeagueDailyPeriodsCoreIds($compatToDate))
            ->groupBy(fn (array $item) => $item['poe_league_id'] . ' ' . $item['date'])
            /** @var Collection<string, array{poe_league_id: int, date: string, id: int}> $collection */
            ->map(static function (Collection $collection) {
                /** @var array{poe_league_id: int, date: string, id: int} $first */
                $first = $collection->first();

                return [
                    'poe_league_id' => $first['poe_league_id'],
                    'date' => $first['date'],
                    'ids' => $collection->pluck('id')->toArray(),
                ];
            })
        ;

        DB::beginTransaction();

        try {
            /** @var array{poe_league_id: int, date: string, ids: int[]} $coreData */
            foreach ($cores as $coreData) {
                $this->currencyCompactRepository->makeCompactCore(
                    $coreData['poe_league_id'],
                    Carbon::parse($coreData['date'], 'UTC'),
                    $this->currencyPriceRepository->getAvgCorePricesData($coreData['ids']),
                    $this->priceCheckCoreRepository->getAvgCoreTypesData($coreData['ids']),
                );

                $this->priceCheckCoreRepository->deleteMany($coreData['ids']);

                $this->priceCheckCoreService->flushLeagueId($coreData['poe_league_id']);
            }
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        DB::commit();
    }
}
