<?php

namespace App\Repositories\Currency\CurrencyPrice;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyPriceWithTypeDto;
use App\Models\Currency\CurrencyPrice as CurrencyPriceModel;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Support\Collection;

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
                    CurrencyPriceDto::class,
                    $currencyPrice->toArray()
                )
            );
        });

        return $result;
    }

    public function find(int $id): CurrencyPriceDto
    {
        return $this->mapper->mapFromArray(
            CurrencyPriceDto::class,
            parent::find($id)->toArray()
        );
    }

    public function create(array $params): CurrencyPriceDto
    {
        return $this->mapper->mapFromArray(
            CurrencyPriceDto::class,
            parent::create($params)->toArray()
        );
    }

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyPriceDto
    {
        return $this->mapper->mapFromArray(
            CurrencyPriceDto::class,
            parent::updateOrCreate($paramsToMatch, $params)->toArray()
        );
    }

    public function findByCurrencyTypeId(int $currencyPriceTypeId): CurrencyPriceWithTypeDto
    {
        return $this->mapper->mapFromArray(
            CurrencyPriceWithTypeDto::class,
            $this->getQuery()
                ->where('currency_type_id', $currencyPriceTypeId)
                ->with('type')
                ->firstOrFail()
                ->toArray()
        );
    }
}
