<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\RepositoryObjects\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeagueDto;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyTypeRepositoryContract extends BaseRepositoryContract
{
    public function findByDetailId(string $detailId): CurrencyTypeWithLeagueDto;

    /**
     * @return Collection<CurrencyTypeWithLeagueDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<CurrencyTypeDto>
     */
    public function getAllByLeague(int $leagueId): Collection;

    public function find(int $id): CurrencyTypeWithLeagueDto;

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
