<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Parameter;
use App\Repositories\Interfaces\ParameterRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Parameter Repository
 * 
 * Handles all Parameter database operations.
 */
class ParameterRepository extends BaseRepository implements ParameterRepositoryInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return Parameter::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaginatedWithFilters(?string $keyFilter = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->newQuery();

        if ($keyFilter) {
            $query->where('key', 'like', '%' . $keyFilter . '%');
        }

        return $query->orderBy('key')->paginate($perPage)->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function findByKey(string $key): ?Parameter
    {
        return $this->newQuery()->where('key', $key)->first();
    }
}
