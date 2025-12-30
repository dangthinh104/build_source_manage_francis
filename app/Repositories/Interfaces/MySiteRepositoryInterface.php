<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\MySite;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * MySite Repository Interface
 * 
 * Defines MySite-specific repository operations.
 * Extends base RepositoryInterface with domain-specific methods.
 */
interface MySiteRepositoryInterface extends RepositoryInterface
{
    /**
     * Get sites with their last builder relationship and paginate
     *
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getSitesWithBuilder(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find site by site name
     *
     * @param string $siteName Unique site name
     * @return MySite|null
     */
    public function findBySiteName(string $siteName): ?MySite;

    /**
     * Get site with last builder relationship
     *
     * @param int $siteId Site ID
     * @return MySite
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getSiteWithBuilder(int $siteId): MySite;

    /**
     * Get all sites ordered by creation date
     *
     * @param string $direction Sort direction (asc|desc)
     * @return Collection<int, MySite>
     */
    public function getAllOrderedByCreation(string $direction = 'desc'): Collection;

    /**
     * Check if site name exists (for uniqueness validation)
     *
     * @param string $siteName Site name to check
     * @param int|null $excludeId Exclude this ID from check (for updates)
     * @return bool
     */
    public function siteNameExists(string $siteName, ?int $excludeId = null): bool;

    /**
     * Check if port is in use
     *
     * @param string $port Port number to check
     * @param int|null $excludeId Exclude this ID from check (for updates)
     * @return bool
     */
    public function portExists(string $port, ?int $excludeId = null): bool;

    /**
     * Check if path is in use
     *
     * @param string $path Source code path to check
     * @param int|null $excludeId Exclude this ID from check (for updates)
     * @return bool
     */
    public function pathExists(string $path, ?int $excludeId = null): bool;

    /**
     * Update site build timestamps
     *
     * @param int $siteId Site ID
     * @param string $status Build status (success|fail)
     * @param int|null $userId User ID who triggered the build
     * @return bool
     */
    public function updateBuildStatus(int $siteId, string $status, ?int $userId = null): bool;

    /**
     * Get sites by build group
     *
     * @param int $buildGroupId Build group ID
     * @return Collection<int, MySite>
     */
    public function getSitesByBuildGroup(int $buildGroupId): Collection;

    /**
     * Get site with all relationships (builder, buildHistories, envVariables)
     *
     * @param int $siteId Site ID
     * @return MySite
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getSiteWithRelations(int $siteId): MySite;
}
