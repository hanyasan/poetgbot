<?php

namespace App\Services\DataServices\Currency\CurrencyTypeService;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
use Illuminate\Support\Collection;

interface CurrencyTypeServiceContract
{

    public function delete(int $id): bool;

    public function findByDetail(string $detailId): CurrencyTypeWithLeagueDto;

    /**
     * @return Collection<CurrencyTypeWithLeagueDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<CurrencyTypeDto>
     */
    public function getAllByLeague(int $leagueId): Collection;

    public function upsertByNinja(int $leagueId): void;

    public function freshAllByNinja(): void;
}
