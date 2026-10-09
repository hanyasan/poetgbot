<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeague;
use App\Models\PoeLeague as CurrencyTypeModel;
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

    public function findByDetailId(string $detailId): CurrencyTypeWithLeague
    {
        return $this->mapper->mapFromArray(
            CurrencyTypeWithLeague::class,
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
                    CurrencyTypeWithLeague::class,
                    $priceType->toArray()
                )
            );
        });

        return $result;
    }

    public function find(int $id): CurrencyTypeWithLeague
    {
        return $this->mapper->mapFromArray(
            CurrencyTypeWithLeague::class,
            parent::find($id)->toArray()
        );
    }

    public function create(array $params): CurrencyTypeWithLeague
    {
        return $this->mapper->mapFromArray(
            CurrencyTypeWithLeague::class,
            parent::create($params)->toArray()
        );
    }

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyTypeWithLeague
    {
        return $this->mapper->mapFromArray(
            CurrencyTypeWithLeague::class,
            parent::updateOrCreate($paramsToMatch, $params)->toArray()
        );
    }
}
