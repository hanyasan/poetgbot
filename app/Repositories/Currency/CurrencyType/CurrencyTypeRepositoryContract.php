<?php

namespace App\Repositories\Currency\CurrencyType;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface CurrencyTypeRepositoryContract extends BaseRepositoryContract
{
    /**
     * @return Collection<int, CurrencyTypeWithLeagueDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<int, CurrencyTypeDto>
     */
    public function getAllByLeague(int $leagueId): Collection;

    /**
     * @return Collection<int, CurrencyTypeWithPricesDto>
     */
    public function getAllByLeagueWithLatestPrice(int $leagueId): Collection;

    /**
     * @return Collection<int, CurrencyTypeWithPricesDto>
     */
    public function getAllByLeagueWithPriceByCore(int $leagueId, int $coreId): Collection;

    #[\Override]
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
