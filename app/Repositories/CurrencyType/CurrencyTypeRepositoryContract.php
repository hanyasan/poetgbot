<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\CurrencyRepository\Currency;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyTypeRepositoryContract extends BaseRepositoryContract
{
    public function findByDetailId(string $detailId): Currency;

    public function getAll(): Collection;

    public function find(int $id): Currency;

    public function create(array $params): Currency;

    public function updateOrCreate(array $params): Currency;
}
