<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\BuildHistory;
use App\Repositories\Interfaces\BuildHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Build History Repository
 * 
 * Handles all BuildHistory database operations.
 */
class BuildHistoryRepository extends BaseRepository implements BuildHistoryRepositoryInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return BuildHistory::class;
    }

    /**
     * Get build histories for a specific site
     *
     * @param int $siteId
     * @return Collection
     */
    public function getBySiteId(int $siteId): Collection
    {
        return $this->newQuery()
            ->where('site_id', $siteId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get latest build for a specific site
     *
     * @param int $siteId
     * @return BuildHistory|null
     */
    public function getLatestBySiteId(int $siteId): ?BuildHistory
    {
        return $this->newQuery()
            ->where('site_id', $siteId)
            ->latest()
            ->first();
    }

    /**
     * Get formatted build histories for a site
     * 
     * Returns array with id, status, excerpt, created_at, user_name, output_log
     *
     * @param int $siteId
     * @return array
     */
    public function getFormattedBySiteId(int $siteId): array
    {
        $histories = $this->getBySiteId($siteId);

        return $histories->map(function ($h) {
            return [
                'id' => $h->id,
                'status' => $h->status,
                'output_excerpt' => substr($h->output_log, 0, 100),
                'created_at' => $h->created_at,
                'user_name' => $h->user ? $h->user->name : 'System',
                'output_log' => $h->output_log,
            ];
        })->toArray();
    }
}
