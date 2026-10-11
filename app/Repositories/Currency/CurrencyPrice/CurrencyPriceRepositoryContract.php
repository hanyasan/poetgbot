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
}
