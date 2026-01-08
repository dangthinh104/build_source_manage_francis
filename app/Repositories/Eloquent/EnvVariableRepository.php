<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\EnvVariable;
use App\Repositories\Interfaces\EnvVariableRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * EnvVariable Repository
 * 
 * Handles all EnvVariable database operations with scope-based queries.
 */
class EnvVariableRepository extends BaseRepository implements EnvVariableRepositoryInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return EnvVariable::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaginatedWithFilters(?string $variableNameFilter = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->newQuery()->with('mySite');

        if ($variableNameFilter) {
            $query->where('variable_name', 'like', '%' . $variableNameFilter . '%');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Find global variable by name
     * 
     * Global variables have no my_site_id and no group_name
     *
     * @param string $variableName
     * @return EnvVariable|null
     */
    public function findGlobalByName(string $variableName): ?EnvVariable
    {
        return $this->newQuery()
            ->where('variable_name', $variableName)
            ->whereNull('my_site_id')
            ->whereNull('group_name')
            ->first();
    }

    /**
     * Find site-scoped variable by site ID and name
     *
     * @param int $siteId
     * @param string $variableName
     * @return EnvVariable|null
     */
    public function findBySiteAndName(int $siteId, string $variableName): ?EnvVariable
    {
        return $this->newQuery()
            ->where('my_site_id', $siteId)
            ->where('variable_name', $variableName)
            ->first();
    }

    /**
     * Find group-scoped variable by group name and variable name
     * 
     * Group variables have a group_name but no my_site_id
     *
     * @param string $groupName
     * @param string $variableName
     * @return EnvVariable|null
     */
    public function findByGroupAndName(string $groupName, string $variableName): ?EnvVariable
    {
        return $this->newQuery()
            ->where('group_name', $groupName)
            ->where('variable_name', $variableName)
            ->whereNull('my_site_id')
            ->first();
    }
}
