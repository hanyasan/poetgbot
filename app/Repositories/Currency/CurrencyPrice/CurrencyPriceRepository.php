<?php

namespace App\Repositories\Currency\CurrencyPrice;

use App\Models\Currency\CurrencyPrice;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;

class CurrencyPriceRepository extends BaseEloquentRepository implements CurrencyPriceRepositoryContract
{
    public function __construct(
        CurrencyPrice $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    /**
     * @param array<int, array{rate: float}> $pricesData
     * @param int $coreId
     * @param int $currencyTypeId
     */
    public function insert(array $pricesData, int $coreId, int $currencyTypeId): void
    {
        $this->model
            ->newQuery()
            ->insert(array_map(function ($pricesData) use ($coreId, $currencyTypeId) {
                return [
                    'rate' => $pricesData['rate'],
                    'currency_type_id' => $currencyTypeId,
                    'price_check_core_id' => $coreId,
                ];
            }, $pricesData));
    }
}
