<?php

namespace App\Repositories\Currency\CurrencyPrice;

use App\Models\Currency\CurrencyPrice;
use App\Models\Currency\CurrencyType;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

final readonly class CurrencyPriceRepository implements CurrencyPriceRepositoryContract
{
    public function __construct(
        private CurrencyPrice $model,
    ) {}

    /**
     * @param array<int, array{
     *     rate: float,
     *     price_check_core_id: int,
     *     currency_type_id: int,
     * }> $pricesData
     */
    #[\Override]
    public function insert(array $pricesData): void
    {
        $this->model
            ->newQuery()
            ->insert(array_map(static function ($pricesData) {
                return [
                    'rate' => $pricesData['rate'],
                    'currency_type_id' => $pricesData['currency_type_id'],
                    'price_check_core_id' => $pricesData['price_check_core_id'],
                ];
            }, $pricesData));
    }

    /**
     * @param array<int, int> $coreIds
     * @return array<int, array{
     *     price_avg_rate: float,
     *     currency_type_name: string,
     *     currency_trade_id: string,
     * }>
     */
    #[\Override]
    public function getAvgCorePricesData(array $coreIds): array
    {
        $sub = CurrencyPrice::query()
            ->select([
                'currency_type_id',
                DB::raw('AVG(rate) as price_avg_rate'),
            ])
            ->whereIn('price_check_core_id', $coreIds)
            ->groupBy('currency_type_id');

        return CurrencyType::query()
            ->select([
                'price_avg_rate',
                'currency_type_name',
                'currency_trade_id',
            ])
            ->rightJoinSub($sub, 'avg_prices', function (JoinClause $join) {
                $join->on('avg_prices.currency_type_id', '=', 'currency_types.id');
            })
            ->get()
            ->toBase()
            ->toArray();
    }
}
