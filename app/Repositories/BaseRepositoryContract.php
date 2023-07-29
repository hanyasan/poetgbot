<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryContract
{
    public function delete(int $id): bool;

    public function find(int $id): object;

    public function create(array $params): object;

    public function updateOne(int $id, array $params): bool;

    public function updateOrCreate(array $params);
}
