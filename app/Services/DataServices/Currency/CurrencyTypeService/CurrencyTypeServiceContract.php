<?php

namespace App\Services\DataServices\Currency\CurrencyTypeService;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use Illuminate\Support\Collection;

interface CurrencyTypeServiceContract
{

    public function delete(int $id): bool;

    /**
     * @return Collection<int, CurrencyTypeWithLeagueDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<int, CurrencyTypeDto>
     */
    public function getAllByLeague(int $leagueId): Collection;

    public function upsertByNinja(int $leagueId): void;

    /**
     * @param array<int, array{
     *     currency_type_name: string,
     *     currency_trade_id: string,
     *     currency_ninja_details_id: string,
     * }> $typeData
     * @param int $leagueId
     *
     * @return void
     */
    public function upsert(array $typeData, int $leagueId): void;

    /**
     * @return Collection<int, CurrencyTypeWithPricesDto>
     */
    public function getAllByLeagueWithPriceByCore(int $leagueId, int $coreId): Collection;

    public function freshAllByNinja(): void;

    public function flushAll(): void;

    public function flushId(int $id): void;

    public function flushLeagueId(int $id): void;

    public function flushGet(): void;

}
