<?php

namespace App\Repositories\CurrencyPrice;
use App\DataTransferObjects\Repository\CurrencyPrice;
use App\DataTransferObjects\Repository\CurrencyPriceWithType;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyPriceRepositoryContract extends BaseRepositoryContract
{
    public function getAll(): Collection;

    public function find(int $id): CurrencyPrice;

    public function create(array $params): CurrencyPrice;

    public function updateOrCreate(array $params): CurrencyPrice;

    public function findByCurrencyTypeId(int $currencyPriceTypeId): CurrencyPriceWithType;
}
