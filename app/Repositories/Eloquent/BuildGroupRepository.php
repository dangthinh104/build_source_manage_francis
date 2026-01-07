<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\BuildGroup;
use App\Repositories\Interfaces\BuildGroupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * BuildGroup Repository Implementation
 * 
 * Handles all database operations related to BuildGroup model.
 * Concrete implementation of BuildGroupRepositoryInterface.
 */
class BuildGroupRepository extends BaseRepository implements BuildGroupRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    protected function model(): string
    {
        return BuildGroup::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupsWithSitesAndUser(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['sites', 'user'])
            ->withCount('sites')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupWithSites(int $groupId): BuildGroup
    {
        return $this->newQuery()
            ->with('sites')
            ->findOrFail($groupId);
    }

    /**
     * {@inheritDoc}
     */
    public function getSitesInGroup(int $groupId): Collection
    {
        $group = $this->findOrFail($groupId);
        return $group->sites;
    }

    /**
     * {@inheritDoc}
     */
    public function syncSites(int $groupId, array $siteIds): void
    {
        $group = $this->findOrFail($groupId);
        $group->sites()->sync($siteIds);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserGroups(int $userId): Collection
    {
        return $this->newQuery()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function groupNameExists(string $name, ?int $excludeId = null): bool
    {
        $query = $this->newQuery()->where('name', $name);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllOrderedByCreation(string $direction = 'desc'): Collection
    {
        return $this->newQuery()
            ->orderBy('created_at', $direction)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalCount(): int
    {
        return $this->count();
    }
}
