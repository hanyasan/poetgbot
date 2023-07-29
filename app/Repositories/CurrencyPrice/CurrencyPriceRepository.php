<?php

namespace App\Repositories\CurrencyPrice;

use App\DataTransferObjects\Repository\CurrencyPrice;
use App\DataTransferObjects\Repository\CurrencyPriceWithType;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Support\Collection;
use App\Models\Currency\CurrencyPrice as CurrencyPriceModel;

final class CurrencyPriceRepository extends BaseEloquentRepository implements CurrencyPriceRepositoryContract
{

    public function __construct(
        CurrencyPriceModel $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    public function getAll(): Collection
    {
        $result = collect();

        $this->getQuery()->get()->each(function ($currencyPrice) use ($result) {
            $result->add(
                $this->mapper->mapFromArray(
                    CurrencyPrice::class,
                    $currencyPrice->toArray()
                )
            );
        });

        return $result;
    }

    public function find(int $id): CurrencyPrice
    {
        return $this->mapper->mapFromArray(
            CurrencyPrice::class,
            parent::find($id)->toArray()
        );
    }

    public function create(array $params): CurrencyPrice
    {
        return $this->mapper->mapFromArray(
            CurrencyPrice::class,
            parent::create($params)->toArray()
        );
    }

    public function updateOrCreate(array $params): CurrencyPrice
    {
        return $this->mapper->mapFromArray(
            CurrencyPrice::class,
            parent::updateOrCreate($params)->toArray()
        );
    }

    public function findByCurrencyTypeId(int $currencyPriceTypeId): CurrencyPriceWithType
    {
        return $this->mapper->mapFromArray(
            CurrencyPriceWithType::class,
            $this->getQuery()
                ->where('currency_type_id', $currencyPriceTypeId)
                ->with('type')
                ->firstOrFail()
                ->toArray()
        );
    }
}
