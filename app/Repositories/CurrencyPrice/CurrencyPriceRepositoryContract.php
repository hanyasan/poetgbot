<?php

namespace App\Repositories\CurrencyPrice;
use App\DataTransferObjects\RepositoryObjects\CurrencyPrice;
use App\DataTransferObjects\RepositoryObjects\CurrencyPriceWithType;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyPriceRepositoryContract extends BaseRepositoryContract
{
    public function getAll(): Collection;

    public function find(int $id): CurrencyPrice;

    public function create(array $params): CurrencyPrice;

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyPrice;

    public function findByCurrencyTypeId(int $currencyPriceTypeId): CurrencyPriceWithType;
}
