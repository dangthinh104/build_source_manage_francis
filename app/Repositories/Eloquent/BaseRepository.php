<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Implementation
 * 
 * Abstract class implementing common Eloquent operations.
 * All concrete repositories should extend this class.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * Eloquent Model instance
     */
    protected Model $model;

    /**
     * Query builder instance for method chaining
     */
    protected Builder $query;

    /**
     * Relations to eager load
     *
     * @var array<string>
     */
    protected array $with = [];

    /**
     * Constructor - Initialize model and query builder
     */
    public function __construct()
    {
        $this->makeModel();
        $this->resetQuery();
    }

    /**
     * Specify Model class name
     *
     * @return string Fully qualified model class name
     */
    abstract protected function model(): string;

    /**
     * Make Model instance
     *
     * @return Model
     * @throws \Exception
     */
    protected function makeModel(): Model
    {
        $modelClass = $this->model();

        if (!class_exists($modelClass)) {
            throw new \Exception("Class {$modelClass} does not exist");
        }

        $model = app($modelClass);

        if (!$model instanceof Model) {
            throw new \Exception("Class {$modelClass} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Reset query builder to fresh state
     *
     * @return void
     */
    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
        
        if (!empty($this->with)) {
            $this->query->with($this->with);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        $this->resetQuery();
        return $this->query->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        $this->resetQuery();
        return $this->query->find($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        $this->resetQuery();
        return $this->query->findOrFail($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection
    {
        $this->resetQuery();
        return $this->query->where($field, $value)->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $result = $this->query->paginate($perPage, $columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function with(array|string $relations): static
    {
        $this->with = is_array($relations) ? $relations : [$relations];
        $this->query->with($this->with);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function first(array $columns = ['*']): ?Model
    {
        $result = $this->query->first($columns);
        $this->resetQuery();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        $count = $this->query->count();
        $this->resetQuery();
        return $count;
    }

    /**
     * Execute a query with fresh model instance
     *
     * @return Builder
     */
    protected function newQuery(): Builder
    {
        $this->resetQuery();
        return $this->query;
    }
}
