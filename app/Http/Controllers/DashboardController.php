<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
class DashboardController extends Controller
{
 public function index()
    {
        if (!Gate::allows('isAdmin')) {
            return redirect()->route('logs.index');
        }
        
        $mySite = DB::table('my_site')
            ->selectRaw('
                my_site.id as id, 
                site_name, 
                path_log, 
                sh_content_dir, 
                last_build, 
                my_site.created_at as created_at, 
                my_site.updated_at as updated_at, 
                users.name as name, 
                last_build_success,
                last_build_fail
            ')
            ->join('users', 'my_site.last_user_build', '=', 'users.id')
            ->get();
        
        // Xử lý định dạng thời gian và ghi đè lên biến hiện có
        foreach ($mySite as $site) {
//var_dump($site->last_build_success);
//var_dump($this->formatTimeAgo($site->last_build_success));
            $site->last_build_success = $this->formatTimeAgo($site->last_build_success);
            $site->last_build_fail = $this->formatTimeAgo($site->last_build_fail);
        }
//dd($mySite->toArray());        
        return Inertia::render('Dashboard', [
            'mySite' => $mySite->toArray(),
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
