<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\CurrencyRepository\Currency;
use App\Models\Currency\CurrencyType;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Support\Collection;

final class CurrencyTypeRepository extends BaseEloquentRepository implements CurrencyTypeRepositoryContract
{
    public function __construct(
        CurrencyType $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    public function findByDetailId(string $detailId): Currency
    {
        return $this->mapper->mapFromArray(
            Currency::class,
            $this
                ->getQuery()
                ->where( 'currency_ninja_details_id', $detailId)
                ->firstOrFail()
                ->toArray()
        );
    }

    public function getAll(): Collection
    {
        $result = collect();

        $this->getQuery()->get()->each(function ($priceType) use ($result) {
            $result->add(
                $this->mapper->mapFromArray(
                    Currency::class,
                    $priceType->toArray()
                )
            );
        });

        return $result;
    }

    public function find(int $id): Currency
    {
        return $this->mapper->mapFromArray(
            Currency::class,
            parent::find($id)->toArray()
        );
    }

    public function create(array $params): Currency
    {
        return $this->mapper->mapFromArray(
            Currency::class,
            parent::create($params)->toArray()
        );
    }

    public function updateOrCreate(array $params): Currency
    {
        return $this->mapper->mapFromArray(
            Currency::class,
            parent::updateOrCreate($params)->toArray()
        );
    }
}
