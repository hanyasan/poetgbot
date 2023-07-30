<?php

namespace App\Repositories;

use App\Services\Mapper\MapperService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class BaseEloquentRepository implements BaseRepositoryContract
{
    public function __construct(
        public readonly Model $model,
        protected readonly MapperService $mapper
    ) {}

    protected function getQuery(): Builder
    {
        return $this->model->newQuery();
    }

    public function delete(int $id): bool
    {
        return $this->find($id)->delete();
    }

    public function truncate(): void
    {
        $this->getQuery()->truncate();
    }

    /**
     * @throws ModelNotFoundException<Model>
     */
    public function find(int $id): object
    {
        return $this->getQuery()->findOrFail($id);
    }

    public function create(array $params): object
    {
        return $this->getQuery()->create($params);
    }

    /**
     * @throws Throwable
     */
    public function updateOne(int $id, array $params): bool
    {
        return $this->find($id)->updateOrFail($params);
    }

    public function updateOrCreate(array $paramsToMatch, array $params): object
    {
        return $this->getQuery()->updateOrCreate($paramsToMatch, $params);
    }
}
