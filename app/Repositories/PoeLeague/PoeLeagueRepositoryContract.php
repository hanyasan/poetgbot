<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
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
     * @return Collection<PoeLeagueDto>
     */
    public function getAll(): Collection;

    /**
     * Get all leagues.
     *
     * @return Collection<PoeLeagueDto>
     */
    public function getAllActiveByVersion(PoeVersionEnum $version): Collection;

    /**
     *
     * @return Collection<PoeLeagueDto>
     */
    public function getAllActive(): Collection;

    /**
     * Get all leagues belonging to the given PoE version.
     *
     * @return Collection<PoeLeagueDto>
     */
    public function getByLeague(PoeVersionEnum $version): Collection;

    /**
     * Find a league by its id.
     *
     * @throws ModelNotFoundException<Model> When the league does not exist
     */
    #[\Override]
    public function find(int $id): PoeLeagueDto;

    /**
     * @param  array<int, array{
     *     league_name: string,
     *     poe_version: string,
     *     is_active: bool,
     * }> $paramsWithValues
     */
    public function upsert(array $paramsWithValues): void;
}
