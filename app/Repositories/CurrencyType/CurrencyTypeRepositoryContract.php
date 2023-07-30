<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\Repository\CurrencyType;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyTypeRepositoryContract extends BaseRepositoryContract
{
    public function findByDetailId(string $detailId): CurrencyType;

    public function getAll(): Collection;

    public function find(int $id): CurrencyType;

    public function create(array $params): CurrencyType;

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyType;
}
