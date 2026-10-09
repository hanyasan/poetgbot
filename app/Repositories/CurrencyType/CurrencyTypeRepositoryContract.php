<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\RepositoryObjects\CurrencyType;
use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeague;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyTypeRepositoryContract extends BaseRepositoryContract
{
    public function findByDetailId(string $detailId): CurrencyTypeWithLeague;

    /**
     * @return Collection<CurrencyTypeWithLeague>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<CurrencyType>
     */
    public function getAllByLeague(int $leagueId): Collection;

    public function find(int $id): CurrencyTypeWithLeague;

    public function create(array $params): CurrencyTypeWithLeague;

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyTypeWithLeague;
}
