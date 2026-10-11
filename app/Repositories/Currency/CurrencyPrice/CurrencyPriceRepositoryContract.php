<?php

namespace App\Repositories\Currency\CurrencyPrice;

interface CurrencyPriceRepositoryContract
{
    /**
     * @param array<int, array{rate: float}> $pricesData
     * @param int $coreId
     * @param int $currencyTypeId
     */
    public function insert(array $pricesData, int $coreId, int $currencyTypeId): void;
}
