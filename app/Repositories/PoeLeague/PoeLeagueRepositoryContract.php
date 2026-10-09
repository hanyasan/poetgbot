<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\RepositoryObjects\PoeLeague;
use App\Enums\PoeVersionEnum;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface PoeLeagueRepositoryContract extends BaseRepositoryContract
{
    /**
     * Get all leagues.
     *
     * @return Collection<int, PoeLeague>
     */
    public function getAll(): Collection;

    /**
     * Get all leagues belonging to the given PoE version.
     *
     * @return Collection<int, PoeLeague>
     */
    public function getByLeague(PoeVersionEnum $version): Collection;

    /**
     * Find a league by its id.
     *
     * @throws ModelNotFoundException<Model> When the league does not exist
     */
    public function find(int $id): PoeLeague;

    /**
     * Create a league.
     *
     * @param  array<string, mixed>  $params  Column values, e.g. league_name and poe_version
     */
    public function create(array $params): PoeLeague;

    /**
     * Update the league matching the given attributes, or create it if none matches.
     *
     * @param  array<string, mixed>  $paramsToMatch  Attributes used to look the league up
     * @param  array<string, mixed>  $params  Values to set on the league
     */
    public function updateOrCreate(array $paramsToMatch, array $params): PoeLeague;
}
