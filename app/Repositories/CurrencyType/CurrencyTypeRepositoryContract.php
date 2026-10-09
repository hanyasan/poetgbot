<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeague;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyTypeRepositoryContract extends BaseRepositoryContract
{
    public function findByDetailId(string $detailId): CurrencyTypeWithLeague;

    public function getAll(): Collection;

    public function find(int $id): CurrencyTypeWithLeague;

    public function create(array $params): CurrencyTypeWithLeague;

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyTypeWithLeague;
}
