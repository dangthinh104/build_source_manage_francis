<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use App\Models\BuildHistory;

/**
 * Build History Repository Interface
 * 
 * Defines contract for BuildHistory data access operations.
 */
interface BuildHistoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get build histories for a specific site
     *
     * @param int $siteId
     * @return Collection
     */
    public function getBySiteId(int $siteId): Collection;

    /**
     * Get latest build for a specific site
     *
     * @param int $siteId
     * @return BuildHistory|null
     */
    public function getLatestBySiteId(int $siteId): ?BuildHistory;

    /**
     * Get formatted build histories for a site
     * 
     * Returns array with id, status, excerpt, created_at, user_name, output_log
     *
     * @param int $siteId
     * @return array
     */
    public function getFormattedBySiteId(int $siteId): array;

    /**
     * Create a new build history record with 'queued' status
     *
     * @param int $siteId
     * @param int $userId
     * @return BuildHistory
     */
    public function createQueuedBuild(int $siteId, int $userId): BuildHistory;
}
