<?php

namespace App\Services\DataServices\PoeLeagueService;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Enums\PoeVersionEnum;
use Illuminate\Support\Collection;

interface PoeLeagueServiceContract
{
    public function delete(int $id): bool;

    public function findActiveByNameVersion(string $name, PoeVersionEnum $version): PoeLeagueDto;

    public function find(int $id): PoeLeagueDto;

    /**
     * @return Collection<int, PoeLeagueDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<int, PoeLeagueDto>
     */
    public function getAllActiveByVersion(PoeVersionEnum $version): Collection;

    /**
     * @return Collection<int, PoeLeagueDto>
     */
    public function getAllActive(): Collection;

    /**
     * @return Collection<int, PoeLeagueDto>
     */
    public function getByVersion(PoeVersionEnum $version): Collection;

    public function truncate(): void;

    public function upsertByNinja(PoeVersionEnum $version): void;

    public function flushAll(): void;

    public function flushGet(): void;

    public function flushId(int $id): void;

    public function flushVersion(PoeVersionEnum $version): void;

    public function updateOrCreateRule(int|null $minutesToCheck, int $leagueId): void;

    /**
     * @return Collection<int, PoeLeagueWithLastCoreDto>
     */
    public function getActiveWithLatestCore(): Collection;

}
