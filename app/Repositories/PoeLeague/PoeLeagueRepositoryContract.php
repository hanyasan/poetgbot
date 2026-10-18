<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Enums\PoeVersionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface PoeLeagueRepositoryContract
{
    public function truncate(): void;

    public function delete(int $id): bool;

    /**
     * Get all leagues.
     *
     * @return Collection<int, PoeLeagueDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<int, PoeLeagueDto>
     */
    public function getAllWithoutRules(): Collection;

    /**
     * Get all leagues.
     *
     * @return Collection<int, PoeLeagueDto>
     */
    public function getAllActiveByVersion(PoeVersionEnum $version): Collection;

    /**
     *
     * @return Collection<int, PoeLeagueDto>
     */
    public function getAllActive(): Collection;

    /**
     * Get all leagues belonging to the given PoE version.
     *
     * @return Collection<int, PoeLeagueDto>
     */
    public function getByLeague(PoeVersionEnum $version): Collection;

    /**
     * Find a league by its id.
     *
     * @throws ModelNotFoundException<Model> When the league does not exist
     */
    public function find(int $id): PoeLeagueDto;

    /**
     * @throws ModelNotFoundException<Model> When the league does not exist
     */
    public function findByNameVersion(string $name, PoeVersionEnum $version): PoeLeagueDto;

    /**
     * @param  array<int, array{
     *     league_name: string,
     *     poe_version: string,
     *     is_active: bool,
     * }> $paramsWithValues
     */
    public function upsert(array $paramsWithValues): void;

    /**
     * @return Collection<int, PoeLeagueWithLastCoreDto>
     */
    public function getActiveWithLatestCore(): Collection;
}
