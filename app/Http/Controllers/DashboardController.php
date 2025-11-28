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
        // Statistics
        $totalSites = MySite::count();
        $totalBuilds = BuildHistory::count();
        $successCount = BuildHistory::where('status', 'success')->count();
        $failedCount = BuildHistory::where('status', 'failed')->count();
        $successRate = $totalBuilds > 0 ? round(($successCount / $totalBuilds) * 100, 2) : 0;

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
                    'created_at' => $this->formatTimeAgo($b->created_at),
                ];
            });

        // Builds over last 7 days
        $buildsLast7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->startOfDay();
            $next = (clone $day)->endOfDay();
            $count = BuildHistory::whereBetween('created_at', [$day, $next])->count();
            $buildsLast7[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $day->format('D'),
                'count' => $count,
            ];
        }

        return Inertia::render('Dashboard', [
            'totalSites' => $totalSites,
            'totalBuilds' => $totalBuilds,
            'successRate' => $successRate,
            'failedCount' => $failedCount,
            'topBuilders' => $topBuilders,
            'recentBuilds' => $recentBuilds,
            'buildsLast7' => $buildsLast7,
        ]);
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
