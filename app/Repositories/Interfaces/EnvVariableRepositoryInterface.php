<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\EnvVariable;

/**
 * EnvVariable Repository Interface
 * 
 * Defines contract for EnvVariable data access operations.
 */
interface EnvVariableRepositoryInterface extends RepositoryInterface
{
    /**
     * Find global variable by name
     *
     * @param string $variableName
     * @return EnvVariable|null
     */
    public function findGlobalByName(string $variableName): ?EnvVariable;

    /**
     * Find site-scoped variable by site ID and name
     *
     * @param int $siteId
     * @param string $variableName
     * @return EnvVariable|null
     */
    public function findBySiteAndName(int $siteId, string $variableName): ?EnvVariable;

    /**
     * Find group-scoped variable by group name and variable name
     *
     * @param string $groupName
     * @param string $variableName
     * @return EnvVariable|null
     */
    public function findByGroupAndName(string $groupName, string $variableName): ?EnvVariable;
}
