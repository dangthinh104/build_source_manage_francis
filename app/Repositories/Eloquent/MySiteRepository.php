<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\MySite;
use App\Repositories\Interfaces\MySiteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * MySite Repository Implementation
 * 
 * Handles all database operations related to MySite model.
 * Concrete implementation of MySiteRepositoryInterface.
 */
class MySiteRepository extends BaseRepository implements MySiteRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    protected function model(): string
    {
        return MySite::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getSitesWithBuilder(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with('lastBuilder')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySiteName(string $siteName): ?MySite
    {
        return $this->newQuery()
            ->where('site_name', $siteName)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getSiteWithBuilder(int $siteId): MySite
    {
        return $this->newQuery()
            ->with('lastBuilder')
            ->findOrFail($siteId);
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
    public function siteNameExists(string $siteName, ?int $excludeId = null): bool
    {
        $query = $this->newQuery()->where('site_name', $siteName);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function portExists(string $port, ?int $excludeId = null): bool
    {
        $query = $this->newQuery()->where('port_pm2', $port);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function pathExists(string $path, ?int $excludeId = null): bool
    {
        $query = $this->newQuery()->where('path_source_code', $path);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function updateBuildStatus(int $siteId, string $status, ?int $userId = null): bool
    {
        $data = [
            'last_build' => now(),
        ];

        if ($status === 'success') {
            $data['last_build_success'] = now();
        } elseif ($status === 'fail') {
            $data['last_build_fail'] = now();
        }

        if ($userId !== null) {
            $data['last_user_build'] = $userId;
        }

        return $this->update($siteId, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getSitesByBuildGroup(int $buildGroupId): Collection
    {
        return $this->newQuery()
            ->whereHas('buildGroups', function ($query) use ($buildGroupId) {
                $query->where('build_group_id', $buildGroupId);
            })
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getSiteWithRelations(int $siteId): MySite
    {
        return $this->newQuery()
            ->with(['lastBuilder', 'buildHistories', 'envVariables'])
            ->findOrFail($siteId);
    }

    /**
     * Create site with all required data
     * 
     * This is a convenience method that combines creation with proper defaults
     *
     * @param array<string, mixed> $data Site data
     * @return MySite
     */
    public function createSite(array $data): MySite
    {
        $defaults = [
            'is_generate_env' => 1,
            'last_user_build' => auth()->id(),
        ];

        return $this->create(array_merge($defaults, $data));
    }

    /**
     * Update site and set last_user_build to current user
     *
     * @param int $id Site ID
     * @param array<string, mixed> $data Data to update
     * @return bool
     */
    public function updateSite(int $id, array $data): bool
    {
        $data['last_user_build'] = auth()->id();
        return $this->update($id, $data);
    }

    /**
     * Search sites by name (partial match)
     *
     * @param string $searchTerm Search term
     * @return Collection<int, MySite>
     */
    public function searchByName(string $searchTerm): Collection
    {
        return $this->newQuery()
            ->where('site_name', 'like', '%' . $searchTerm . '%')
            ->get();
    }

    /**
     * Get sites that have PM2 enabled
     *
     * @return Collection<int, MySite>
     */
    public function getSitesWithPM2(): Collection
    {
        return $this->newQuery()
            ->whereNotNull('port_pm2')
            ->where('port_pm2', '!=', '')
            ->get();
    }

    /**
     * Get total count of sites
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->count();
    }
}
