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
     * @param int|array{
     *     poe_version: string,
     *     league_name: string,
     * } $league
     *
     * @return Collection<CurrencyType>
     */
    public function getAllByLeague(int|array $league): Collection;

    /**
     * @param int|array{
     *     poe_version: string,
     *     league_name: string,
     * } $league
     */
    public function upsertByNinja(int|array $league): void;
}
