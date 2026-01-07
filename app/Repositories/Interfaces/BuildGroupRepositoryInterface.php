<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\BuildGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * BuildGroup Repository Interface
 * 
 * Defines BuildGroup-specific repository operations.
 * Extends base RepositoryInterface with domain-specific methods.
 */
interface BuildGroupRepositoryInterface extends RepositoryInterface
{
    /**
     * Get build groups with sites and user relationships, paginated
     *
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getGroupsWithSitesAndUser(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get build group with sites relationship
     *
     * @param int $groupId Build group ID
     * @return BuildGroup
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getGroupWithSites(int $groupId): BuildGroup;

    /**
     * Get all sites in a specific build group
     *
     * @param int $groupId Build group ID
     * @return Collection
     */
    public function getSitesInGroup(int $groupId): Collection;

    /**
     * Sync sites to a build group (updates pivot table)
     *
     * @param int $groupId Build group ID
     * @param array<int> $siteIds Array of site IDs to sync
     * @return void
     */
    public function syncSites(int $groupId, array $siteIds): void;

    /**
     * Get all build groups created by a specific user
     *
     * @param int $userId User ID
     * @return Collection<int, BuildGroup>
     */
    public function getUserGroups(int $userId): Collection;

    /**
     * Check if build group name exists (for uniqueness validation)
     *
     * @param string $name Group name to check
     * @param int|null $excludeId Exclude this ID from check (for updates)
     * @return bool
     */
    public function groupNameExists(string $name, ?int $excludeId = null): bool;

    /**
     * Get build groups ordered by creation date
     *
     * @param string $direction Sort direction (asc|desc)
     * @return Collection<int, BuildGroup>
     */
    public function getAllOrderedByCreation(string $direction = 'desc'): Collection;

    /**
     * Get total count of build groups
     *
     * @return int
     */
    public function getTotalCount(): int;
}
