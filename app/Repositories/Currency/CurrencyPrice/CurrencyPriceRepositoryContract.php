<?php

namespace App\Repositories\Currency\CurrencyPrice;

interface CurrencyPriceRepositoryContract
{
    /**
     * @param array<int, array{
     *     rate: float,
     *     price_check_core_id: int,
     *     currency_type_id: int,
     * }> $pricesData
     */
    public function insert(array $pricesData): void;

    /**
     * @param array<int, int> $coreIds
     * @return array<int, array{
     *     price_avg_rate: float,
     *     currency_type_name: string,
     *     currency_trade_id: string,
     * }>
     */
    public function getAvgCorePricesData(array $coreIds): array;
}
