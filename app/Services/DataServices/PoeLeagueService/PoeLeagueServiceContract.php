<?php

namespace App\Services\DataServices\PoeLeagueService;

use App\DataTransferObjects\RepositoryObjects\PoeLeague;
use App\Enums\PoeVersionEnum;
use Illuminate\Support\Collection;

interface PoeLeagueServiceContract
{
    public function delete(int $id): bool;

    public function find(int $id): PoeLeague;

    /**
     * @return Collection<PoeLeague>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<PoeLeague>
     */
    public function getAllActive(PoeVersionEnum $version): Collection;

    /**
     * @return Collection<PoeLeague>
     */
    public function getByVersion(PoeVersionEnum $version): Collection;

    public function truncate(): void;

    public function upsertByNinja(PoeVersionEnum $version): void;
}
