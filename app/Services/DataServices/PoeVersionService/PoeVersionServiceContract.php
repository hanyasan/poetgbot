<?php

namespace App\Services\DataServices\PoeVersionService;

use App\DataTransferObjects\Repository\PoeLeague;
use App\Enums\PoeVersionEnum;
use Illuminate\Support\Collection;

interface PoeVersionServiceContract
{
    public function create(array $params): PoeLeague;

    public function delete(int $id): bool;

    public function find(int $id): PoeLeague;

    public function getAll(): Collection;

    public function getByVersion(PoeVersionEnum $version): Collection;

    public function updateOrCreate(array $paramsToMatch, array $params): PoeLeague;

    public function truncate(): void;
}
