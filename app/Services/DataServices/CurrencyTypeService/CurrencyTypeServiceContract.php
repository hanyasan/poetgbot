<?php

namespace App\Services\DataServices\CurrencyTypeService;

use App\DataTransferObjects\RepositoryObjects\CurrencyType;
use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeague;
use Illuminate\Support\Collection;

interface CurrencyTypeServiceContract
{

    public function delete(int $id): bool;

    public function findByDetail(string $detailId): CurrencyTypeWithLeague;

    /**
     * @return Collection<CurrencyTypeWithLeague>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<CurrencyType>
     */
    public function getAllByLeague(int $leagueId): Collection;

    public function upsertByNinja(int $leagueId): void;
}
