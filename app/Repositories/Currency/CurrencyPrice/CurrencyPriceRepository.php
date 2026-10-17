<?php

namespace App\Repositories\Currency\CurrencyPrice;

use App\Models\Currency\CurrencyPrice;

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
}
