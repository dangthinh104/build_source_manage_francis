<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Parameter;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Parameter Repository Interface
 * 
 * Defines contract for Parameter data access operations.
 */
interface ParameterRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated parameters with optional key filter
     *
     * @param string|null $keyFilter
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedWithFilters(?string $keyFilter = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * Find parameter by key
     *
     * @param string $key
     * @return Parameter|null
     */
    public function findByKey(string $key): ?Parameter;
}
