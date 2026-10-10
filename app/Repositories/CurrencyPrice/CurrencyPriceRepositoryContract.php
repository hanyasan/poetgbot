<?php

namespace App\Repositories\CurrencyPrice;
use App\DataTransferObjects\RepositoryObjects\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\CurrencyPriceWithTypeDto;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyPriceRepositoryContract extends BaseRepositoryContract
{
    public function getAll(): Collection;

    public function find(int $id): CurrencyPriceDto;

    public function create(array $params): CurrencyPriceDto;

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyPriceDto;

    public function findByCurrencyTypeId(int $currencyPriceTypeId): CurrencyPriceWithTypeDto;
}
