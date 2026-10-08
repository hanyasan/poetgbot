<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\Repository\PoeLeague;
use App\Enums\PoeVersionEnum;
use App\Repositories\BaseRepositoryContract;
use Illuminate\Support\Collection;

interface PoeLeagueRepositoryContract extends BaseRepositoryContract
{
    public function getAll(): Collection;

    public function getByVersion(PoeVersionEnum $version): Collection;

    public function find(int $id): PoeLeague;

    public function create(array $params): PoeLeague;

    public function updateOrCreate(array $paramsToMatch, array $params): PoeLeague;
}
