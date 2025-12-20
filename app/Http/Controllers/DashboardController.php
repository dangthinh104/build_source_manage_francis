<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use App\Models\BuildHistory;
use App\Models\MySite;
use App\Models\User;
class DashboardController extends Controller
{
 public function index()
    {
        if (!Gate::allows('isAdmin')) {
            return redirect()->route('logs.index');
        }

        // Basic Statistics
        $totalSites = MySite::count();
        $totalBuilds = BuildHistory::count();
        $successCount = BuildHistory::where('status', 'success')->count();
        $failedCount = BuildHistory::where('status', 'failed')->count();
        $successRate = $totalBuilds > 0 ? round(($successCount / $totalBuilds) * 100, 2) : 0;

        // Today's builds with comparison
        $todayBuilds = BuildHistory::whereDate('created_at', Carbon::today())->count();
        $yesterdayBuilds = BuildHistory::whereDate('created_at', Carbon::yesterday())->count();
        $buildsTrend = $yesterdayBuilds > 0 
            ? round((($todayBuilds - $yesterdayBuilds) / $yesterdayBuilds) * 100) 
            : ($todayBuilds > 0 ? 100 : 0);

        // Average build duration (from created_at to updated_at)
        $avgDurationSeconds = BuildHistory::whereNotNull('updated_at')
            ->whereColumn('updated_at', '>', 'created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_duration')
            ->value('avg_duration') ?? 0;
        $avgDuration = $this->formatDuration((int) $avgDurationSeconds);

        // Top builders by build count
        $topBuilders = User::select('users.id', 'users.name')
            ->join('build_histories', 'users.id', '=', 'build_histories.user_id')
            ->selectRaw('users.id, users.name, COUNT(build_histories.id) as builds_count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('builds_count')
            ->limit(5)
            ->get();

        // Recent builds
        $recentBuilds = BuildHistory::with(['user', 'site'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'site_name' => $b->site->site_name ?? 'Unknown',
                    'user_name' => $b->user->name ?? 'System',
                    'status' => $b->status,
                    'created_at' => $b->created_at->diffForHumans(),
                ];
            });

        // Site health summary (top 5 sites by build count)
        $siteHealth = MySite::withCount([
            'buildHistories as total_builds',
            'buildHistories as success_builds' => fn($q) => $q->where('status', 'success'),
        ])
        ->orderByDesc('total_builds')
        ->limit(5)
        ->get()
        ->map(fn($s) => [
            'id' => $s->id,
            'site_name' => $s->site_name,
            'total_builds' => $s->total_builds,
            'success_rate' => $s->total_builds > 0 
                ? round(($s->success_builds / $s->total_builds) * 100) 
                : 0,
            'last_build_ago' => $s->last_build_success_ago ?? $s->last_build_fail_ago ?? '—',
        ]);

        // Problem sites (most failures)
        $problemSites = MySite::select('my_site.id', 'my_site.site_name')
            ->join('build_histories', 'my_site.id', '=', 'build_histories.site_id')
            ->where('build_histories.status', 'failed')
            ->selectRaw('my_site.id, my_site.site_name, COUNT(*) as fail_count')
            ->groupBy('my_site.id', 'my_site.site_name')
            ->orderByDesc('fail_count')
            ->limit(3)
            ->get();

        // Enhanced builds last 7 days with success/fail breakdown
        $buildsLast7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->startOfDay();
            $next = (clone $day)->endOfDay();
            $success = BuildHistory::whereBetween('created_at', [$day, $next])
                ->where('status', 'success')->count();
            $failed = BuildHistory::whereBetween('created_at', [$day, $next])
                ->where('status', 'failed')->count();
            $buildsLast7[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $day->format('D'),
                'success' => $success,
                'failed' => $failed,
                'total' => $success + $failed,
            ];
        }

        return Inertia::render('Dashboard', [
            'totalSites' => $totalSites,
            'totalBuilds' => $totalBuilds,
            'successRate' => $successRate,
            'failedCount' => $failedCount,
            'todayBuilds' => $todayBuilds,
            'yesterdayBuilds' => $yesterdayBuilds,
            'buildsTrend' => $buildsTrend,
            'avgDuration' => $avgDuration,
            'topBuilders' => $topBuilders,
            'recentBuilds' => $recentBuilds,
            'buildsLast7' => $buildsLast7,
            'siteHealth' => $siteHealth,
            'problemSites' => $problemSites,
        ]);
    }

    /**
     * Format duration in seconds to human readable format
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        if ($minutes < 60) {
            return $secs > 0 ? "{$minutes}m {$secs}s" : "{$minutes}m";
        }
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h {$mins}m";
    }

    /**
     * Format thời gian theo yêu cầu
     * 
     * @param string $dateTime
     * @return string
     */
    private function formatTimeAgo($dateTime)
{
    if (empty($dateTime)) {
        return 'N/A';
    }
    
    $carbon = Carbon::parse($dateTime);
    $now = Carbon::now();
  
    $diffInDays = floor($carbon->diffInDays($now));
    
    if ($diffInDays > 0) {
        return $diffInDays . ' days ago';
    } else {
        $diffInHours = floor($carbon->diffInHours($now));
        $diffInMinutes = $carbon->diffInMinutes($now) % 60;
        $diffInSeconds = $carbon->diffInSeconds($now) % 60;
        
        return $diffInHours . ' hours ' . $diffInMinutes . ' min ' . $diffInSeconds . ' s ago';
    }
}
}
