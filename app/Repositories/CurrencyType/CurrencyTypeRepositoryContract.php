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

    /**
     * @param array<int, array{
     *   currency_type_name: string,
     *   currency_trade_id: string,
     *   currency_ninja_details_id: string,
     *   poe_league_id: int,
     * }> $insertData
     *
     * @return void
     */
    public function upsert(array $insertData): void;
}
