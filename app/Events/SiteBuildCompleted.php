<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\BuildHistory;
use App\Models\MySite;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a site build completes (success or failure).
 */
class SiteBuildCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param BuildHistory $history The build history record
     * @param MySite $site The site that was built
     * @param string $status Build status: 'success' or 'failed'
     */
    public function __construct(
        public readonly BuildHistory $history,
        public readonly MySite $site,
        public readonly string $status
    ) {}
}
