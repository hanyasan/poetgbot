<?php

namespace App\Services\DataServices\PoeLeagueService;

use App\DataTransferObjects\RepositoryObjects\PoeLeague;
use App\Enums\PoeVersionEnum;
use Illuminate\Support\Collection;

interface PoeLeagueServiceContract
{
    public function create(array $params): PoeLeague;

    public function delete(int $id): bool;

    public function find(int $id): PoeLeague;

    public function getAll(): Collection;

    public function getByVersion(PoeVersionEnum $version): Collection;

    public function updateOrCreate(array $paramsToMatch, array $params): PoeLeague;

    public function truncate(): void;

    public function upsertByNinja(PoeVersionEnum $version): void;

    /**
     * @param  array<int, array{
     *     league_name: string,
     *     poe_version: string,
     *     is_active: bool,
     * }> $paramsWithValues
     */
    public function upsert(array $paramsWithValues): void;
}
