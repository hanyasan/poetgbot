<?php

namespace App\Services\DataServices\CurrencyTypeService;

use App\DataTransferObjects\CurrencyRepository\Currency;
use App\Repositories\CurrencyType\CurrencyTypeRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CurrencyTypeService implements CurrencyTypeServiceContract
{
    public function __construct(
        private readonly CurrencyTypeRepositoryContract $repository
    ) {}

    public function create(array $params): Currency
    {
        return $this->repository->create($params);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function findByDetail(string $detailId): Currency
    {
        return Cache::tags([
            'currency_type',
            'currency_type_detail:' . $detailId
        ])->remember(
            'by_detail',
            60 * 60,
            function () use ($detailId) {
                return $this->repository->findByDetailId($detailId);
            }
        );
    }

    public function getAll(): Collection
    {
        return Cache::tags([
            'currency_type'
        ])->remember(
            'all',
            60 * 60,
            function () {
                return $this->repository->getAll();
            }
        );
    }

    public function updateOrCreate(array $params): Currency
    {
        return $this->repository->updateOrCreate($params);
    }
}
