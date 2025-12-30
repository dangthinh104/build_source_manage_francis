<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 * 
 * Defines common CRUD operations that all repositories must implement.
 * Following Repository Pattern to abstract data layer from business logic.
 */
interface RepositoryInterface
{
    /**
     * Get all records
     *
     * @param array<string> $columns Columns to select
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Find a record by ID
     *
     * @param int $id Primary key
     * @param array<string> $columns Columns to select
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or throw exception
     *
     * @param int $id Primary key
     * @param array<string> $columns Columns to select
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Find records by specific field
     *
     * @param string $field Column name
     * @param mixed $value Value to search
     * @param array<string> $columns Columns to select
     * @return Collection<int, Model>
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection;

    /**
     * Create a new record
     *
     * @param array<string, mixed> $data Data to create
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record by ID
     *
     * @param int $id Primary key
     * @param array<string, mixed> $data Data to update
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record by ID
     *
     * @param int $id Primary key
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get paginated records
     *
     * @param int $perPage Items per page
     * @param array<string> $columns Columns to select
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Eager load relationships
     *
     * @param array<string>|string $relations Relationship names
     * @return static
     */
    public function with(array|string $relations): static;

    /**
     * Order by column
     *
     * @param string $column Column name
     * @param string $direction Sort direction (asc|desc)
     * @return static
     */
    public function orderBy(string $column, string $direction = 'asc'): static;

    /**
     * Add where clause
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value
     * @param mixed $value Value (optional)
     * @return static
     */
    public function where(string $column, mixed $operator, mixed $value = null): static;

    /**
     * Get first record matching criteria
     *
     * @param array<string> $columns Columns to select
     * @return Model|null
     */
    public function first(array $columns = ['*']): ?Model;

    /**
     * Get count of records
     *
     * @return int
     */
    public function count(): int;
}
