<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\Repository\CurrencyType;
use App\Models\Currency\CurrencyType as CurrencyTypeModel;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Support\Collection;

final class CurrencyTypeRepository extends BaseEloquentRepository implements CurrencyTypeRepositoryContract
{
    public function __construct(
        CurrencyTypeModel $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    public function findByDetailId(string $detailId): CurrencyType
    {
        return $this->mapper->mapFromArray(
            CurrencyType::class,
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
                    CurrencyType::class,
                    $priceType->toArray()
                )
            );
        });

        return $result;
    }

    public function find(int $id): CurrencyType
    {
        return $this->mapper->mapFromArray(
            CurrencyType::class,
            parent::find($id)->toArray()
        );
    }

    public function create(array $params): CurrencyType
    {
        return $this->mapper->mapFromArray(
            CurrencyType::class,
            parent::create($params)->toArray()
        );
    }

    public function updateOrCreate(array $params): CurrencyType
    {
        return $this->mapper->mapFromArray(
            CurrencyType::class,
            parent::updateOrCreate($params)->toArray()
        );
    }
}
