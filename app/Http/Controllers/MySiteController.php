<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSiteBuild;
use App\Models\BuildHistory;
use App\Services\SiteBuildService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class MySiteController extends Controller
{
    protected $siteBuildService;

    public function __construct(SiteBuildService $siteBuildService)
    {
        $this->siteBuildService = $siteBuildService;
    }

    public function index()
    {
        $sites = \App\Models\MySite::with('lastBuilder')->orderByDesc('created_at')->paginate(15);

        return inertia('MySites/Index', [
            'sites' => $sites,
        ]);
    }

    public function show($id)
    {
        $site = \App\Models\MySite::findOrFail($id);
        $buildHistories = \App\Models\BuildHistory::where('site_id', $id)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        return inertia('MySites/Show', [
            'site' => $site,
            'buildHistories' => $buildHistories,
        ]);
    }

    public function store(Request $request)
    {
        // Only super_admin can create new sites
        if (!auth()->user() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can create new sites.');
        }

        $request->validate([
            'site_name' => 'required|string|max:255|unique:my_site,site_name',
            'folder_source_path' => ['required', 'string', 'max:500', 'regex:/^\/[a-zA-Z0-9\/_-]+$/', 'not_regex:/\\.\\.\\/|\\\\./', 'unique:my_site,path_source_code'],
            'include_pm2' => 'boolean',
            'port_pm2' => 'nullable|max:10|unique:my_site,port_pm2',
        ]);

        try {
            $this->siteBuildService->createSite(
                $request->input('site_name'),
                $request->input('folder_source_path'),
                $request->input('include_pm2', false),
                $request->input('port_pm2')
            );

            return redirect()->route('my_site.index')->with('success', 'Site created successfully!');
        } catch (\Exception $e) {
            return redirect()->route('my_site.index')->with('error', 'Failed to create site: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        // Admin and Super Admin can edit sites
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to edit sites.'
            ], 403);
        }

        $request->validate([
            'id' => 'required|integer',
            'site_name' => 'nullable|string|max:255|unique:my_site,site_name,' . $request->id,
            'port_pm2' => 'nullable|unique:my_site,port_pm2,' . $request->id,
            'api_endpoint_url' => 'nullable|string',
        ]);

        try {
            $updateData = array_filter([
                'site_name' => $request->input('site_name'),
                'port_pm2' => $request->input('port_pm2'),
                'api_endpoint_url' => $request->input('api_endpoint_url'),
            ], function($value) {
                return $value !== null;
            });

            $this->siteBuildService->updateSite($request->id, $updateData);

            return response()->json([
                'status' => true,
                'message' => 'Site updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update site: ' . $e->getMessage()
            ], 400);
        }
    }

    public function buildMySite(Request $request)
    {
        // Only admin and super_admin can build sites
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            return response()->json([
                'status' => 0,
                'message' => 'Forbidden. Only Admin or Super Admin can build sites.'
            ], 403);
        }

        try {
            $request->validate([
                'site_id' => 'required|integer|exists:my_site,id',
            ]);

            // Dispatch async job instead of synchronous build
            ProcessSiteBuild::dispatch($request->site_id, auth()->id());

            return response()->json([
                'status' => 'queued',
                'message' => 'Build has been queued for processing. Check build history for status updates.'
            ]);
        } catch (\Exception $exp) {
            return response()->json([
                'status' => 0,
                'message' => $exp->getMessage()
            ], 400);
        }
    }

    /**
     * Get the current build status for a site.
     * Used for frontend polling to track async build progress.
     */
    public function getBuildStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'site_id' => 'required|integer|exists:my_site,id',
            ]);

            $latestBuild = BuildHistory::where('site_id', $request->site_id)
                ->latest()
                ->first();

            return response()->json([
                'status' => $latestBuild?->status ?? 'unknown',
                'updated_at' => $latestBuild?->updated_at?->toIso8601String(),
                'history_id' => $latestBuild?->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getLogLastBuildByID(Request $request): JsonResponse
    {
        try {
            if (!$request->has('site_id')) {
                throw new \Exception('Site ID is required.');
            }

            $data = $this->siteBuildService->getLogContent($request->site_id);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function buildMySiteOutbound(string $siteName, Request $request)
    {
        try {
            if (!$siteName) {
                throw new \Exception('Site name is required.');
            }

            $result = $this->siteBuildService->buildSiteByName(
                $siteName,
                $request->input('user')
            );

            // Send notification email if build was successful
            if ($result['status'] && $request->has('user')) {
                $siteObject = \DB::table('my_site')->where('site_name', $siteName)->first();
                $user = \DB::table('users')->where('name', $request->user)->first();

                if ($siteObject && $user) {
                    $this->siteBuildService->sendBuildNotification(
                        $siteObject,
                        $result['log_path'],
                        $user->email
                    );
                }
            }

            return response()->json([
                'status' => 200,
                'message' => 'Build completed successfully'
            ]);
        } catch (\Exception $exp) {
            return response()->json([
                'status' => $exp->getCode() ?: 500,
                'message' => $exp->getMessage()
            ], 400);
        }
    }

    public function getAllDetailSiteByID(Request $request): JsonResponse
    {
        try {
            if (!$request->has('site_id')) {
                throw new \Exception('Site ID is required.');
            }

            $data = $this->siteBuildService->getSiteDetails($request->site_id);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getBuildHistoryBySite(Request $request): JsonResponse
    {
        try {
            if (!$request->has('site_id')) {
                throw new \Exception('Site ID is required.');
            }

            $siteId = $request->input('site_id');

            $histories = \App\Models\BuildHistory::where('site_id', $siteId)
                ->with('user')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($h) {
                    return [
                        'id' => $h->id,
                        'status' => $h->status,
                        'output_excerpt' => substr($h->output_log, 0, 100),
                        'created_at' => $h->created_at,
                        'user_name' => $h->user ? $h->user->name : 'System',
                        'output_log' => $h->output_log,
                    ];
                });

            return response()->json(['histories' => $histories]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getSiteLogs(Request $request): JsonResponse
    {
        try {
            $request->validate(['site_id' => 'required|integer']);
            $siteId = $request->input('site_id');

            $site = \DB::table('my_site')->where('id', $siteId)->first();
            if (!$site) {
                throw new \Exception('Site not found');
            }

            // Get log directory path for this site
            $storage = Storage::disk('my_site_storage');
            $logDirectory = $site->site_name . '/log';

            if (!$storage->exists($logDirectory)) {
                return response()->json(['logs' => []]);
            }

            // Get all .log files in the directory
            $files = $storage->files($logDirectory);
            $logs = [];

            foreach ($files as $file) {
                if (str_ends_with($file, '.log')) {
                    $filename = basename($file);
                    $lastModified = $storage->lastModified($file);

                    $logs[] = [
                        'filename' => $filename,
                        'path' => $file,
                        'date' => date('Y-m-d H:i:s', $lastModified),
                        'timestamp' => $lastModified,
                    ];
                }
            }

            // Sort by timestamp (newest first)
            usort($logs, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

            return response()->json(['logs' => $logs]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function viewLogFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'site_id' => 'required|integer',
                'log_path' => 'required|string',
            ]);

            $siteId = $request->input('site_id');
            $logPath = $request->input('log_path');

            $site = \DB::table('my_site')->where('id', $siteId)->first();
            if (!$site) {
                throw new \Exception('Site not found');
            }

            $storage = Storage::disk('my_site_storage');

            // Security check: ensure the log path belongs to this site
            if (!str_starts_with($logPath, $site->site_name . '/log/')) {
                throw new \Exception('Invalid log path');
            }

            if (!$storage->exists($logPath)) {
                throw new \Exception('Log file not found');
            }

            $content = $storage->get($logPath);

            return response()->json([
                'filename' => basename($logPath),
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteSite(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || ($user->role ?? '') !== 'super_admin') {
                throw new \Exception('Unauthorized', 403);
            }

            $request->validate([ 'site_id' => 'required|integer' ]);
            $siteId = $request->input('site_id');

            $site = \DB::table('my_site')->where('id', $siteId)->first();
            if (!$site) {
                throw new \Exception('Site not found');
            }

            $service = app(\App\Services\SiteDestructionService::class);
            $destroyResult = $service->destroy($site->path_source_code, $site->site_name);

            if ($destroyResult['success']) {
                \DB::table('my_site')->where('id', $siteId)->delete();
                return response()->json(['status' => true, 'messages' => $destroyResult['messages']]);
            }

            return response()->json(['status' => false, 'messages' => $destroyResult['messages']], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
