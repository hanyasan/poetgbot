<?php

namespace App\Services\DataServices\PoeLeagueService;

use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use Illuminate\Support\Collection;

interface PoeLeagueServiceContract
{
    public function delete(int $id): bool;

    public function find(int $id): PoeLeagueDto;

    /**
     * @return Collection<PoeLeagueDto>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<PoeLeagueDto>
     */
    public function getAllActive(PoeVersionEnum $version): Collection;

    /**
     * @return Collection<PoeLeagueDto>
     */
    public function getByVersion(PoeVersionEnum $version): Collection;

    public function truncate(): void;

    public function upsertByNinja(PoeVersionEnum $version): void;

    public function flushAll(): void;

    public function flushGet(): void;

    public function flushId(int $id): void;

    public function flushVersion(PoeVersionEnum $version): void;

}
