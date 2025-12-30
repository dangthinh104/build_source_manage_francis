<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessSiteBuild;
use App\Repositories\Interfaces\BuildHistoryRepositoryInterface;
use App\Repositories\Interfaces\MySiteRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\MySiteStorageService;
use App\Services\SiteBuildService;
use App\Services\SiteDestructionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * MySite Controller
 *
 * Handles HTTP requests for site management.
 * Delegates data operations to MySiteRepository.
 * Delegates business logic to SiteBuildService.
 */
class MySiteController extends BaseController
{
    /**
     * Constructor - Inject dependencies
     *
     * @param MySiteRepositoryInterface $mySiteRepository
     * @param BuildHistoryRepositoryInterface $buildHistoryRepository
     * @param UserRepositoryInterface $userRepository
     * @param MySiteStorageService $storage
     * @param SiteBuildService $siteBuildService
     */
    public function __construct(
        protected MySiteRepositoryInterface $mySiteRepository,
        protected BuildHistoryRepositoryInterface $buildHistoryRepository,
        protected UserRepositoryInterface $userRepository,
        protected MySiteStorageService $storage,
        protected SiteBuildService $siteBuildService
    ) {}

    /**
     * Display a paginated list of sites
     *
     * @return Response
     */
    public function index(): Response
    {
        $sites = $this->mySiteRepository->getSitesWithBuilder(15);

        return inertia('MySites/Index', [
            'sites' => $sites,
        ]);
    }

    /**
     * Display specific site with build histories
     *
     * @param int $id Site ID
     * @return Response
     */
    public function show(int $id): Response
    {
        $site = $this->mySiteRepository->findOrFail($id);

        $buildHistories = $this->buildHistoryRepository->getBySiteId($id);

        return inertia('MySites/Show', [
            'site' => $site,
            'buildHistories' => $buildHistories,
        ]);
    }

    /**
     * Create a new site
     *
     * Only super_admin can create sites.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Authorization check
        if (!auth()->user() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can create new sites.');
        }

        // Validation
        $request->validate([
            'site_name' => 'required|string|max:255|unique:my_site,site_name',
            'folder_source_path' => [
                'required',
                'string',
                'max:500',
                'regex:/^\/[a-zA-Z0-9\/_-]+$/',
                'not_regex:/\.\.\/|\\\./',
                'unique:my_site,path_source_code'
            ],
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

            return $this->redirectWithSuccess('my_site.index', 'Site created successfully!');
        } catch (\Exception $e) {
            return $this->redirectWithError('my_site.index', 'Failed to create site: ' . $e->getMessage());
        }
    }

    /**
     * Update site configuration
     *
     * Admin and Super Admin can edit sites.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        // Authorization check
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            return $this->forbidden('You do not have permission to edit sites.');
        }

        // Validation
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

            $this->mySiteRepository->updateSite($request->id, $updateData);

            return $this->success(null, 'Site updated successfully!');
        } catch (\Exception $e) {
            return $this->error('Failed to update site: ' . $e->getMessage());
        }
    }

    /**
     * Queue a site build job
     *
     * Only admin and super_admin can build sites.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function buildMySite(Request $request): JsonResponse
    {
        // Authorization check
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            return $this->forbidden('Only Admin or Super Admin can build sites.');
        }

        try {
            $request->validate([
                'site_id' => 'required|integer|exists:my_site,id',
            ]);

            // Dispatch async job
            ProcessSiteBuild::dispatch($request->site_id, auth()->id());

            return $this->success(
                ['status' => 'queued'],
                'Build has been queued for processing. Check build history for status updates.'
            );
        } catch (\Exception $exp) {
            return $this->error($exp->getMessage());
        }
    }

    /**
     * Get the current build status for a site
     *
     * Used for frontend polling to track async build progress.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBuildStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'site_id' => 'required|integer|exists:my_site,id',
            ]);

            $latestBuild = $this->buildHistoryRepository->getLatestBySiteId($request->site_id);

            return $this->success([
                'status' => $latestBuild?->status ?? 'unknown',
                'updated_at' => $latestBuild?->updated_at?->toIso8601String(),
                'history_id' => $latestBuild?->id,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get last build log content for a site
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLogLastBuildByID(Request $request): JsonResponse
    {
        try {
            if (!$request->has('site_id')) {
                throw new \Exception('Site ID is required.');
            }

            $data = $this->siteBuildService->getLogContent($request->site_id);

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get all site details including shell script and env content
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllDetailSiteByID(Request $request): JsonResponse
    {
        try {
            if (!$request->has('site_id')) {
                throw new \Exception('Site ID is required.');
            }

            $data = $this->siteBuildService->getSiteDetails($request->site_id);

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get build history for a specific site
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBuildHistoryBySite(Request $request): JsonResponse
    {
        try {
            if (!$request->has('site_id')) {
                throw new \Exception('Site ID is required.');
            }

            $siteId = $request->input('site_id');

            $histories = $this->buildHistoryRepository->getFormattedBySiteId($siteId);

            return $this->success(['histories' => $histories]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get all log files for a site
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSiteLogs(Request $request): JsonResponse
    {
        try {
            $request->validate(['site_id' => 'required|integer']);
            $siteId = $request->input('site_id');

            $site = $this->mySiteRepository->findOrFail($siteId);

            // Get a log directory path for this site
            $logDirectory = $this->storage->getLogDirectory($site->site_name);

            if (!$this->storage->exists($logDirectory)) {
                return $this->success(['logs' => []]);
            }

            // Get all .log files in the directory
            $files = $this->storage->files($logDirectory);
            $logs = [];

            foreach ($files as $file) {
                if (str_ends_with($file, '.log')) {
                    $filename = basename($file);
                    $lastModified = $this->storage->lastModified($file);

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

            return $this->success(['logs' => $logs]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * View the content of a specific log file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function viewLogFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'site_id' => 'required|integer',
                'log_path' => 'required|string',
            ]);

            $siteId = $request->input('site_id');
            $logPath = $request->input('log_path');

            $site = $this->mySiteRepository->findOrFail($siteId);

            // Security check: ensure the log path belongs to this site
            if (!str_starts_with($logPath, $site->site_name . '/log/')) {
                throw new \Exception('Invalid log path');
            }

            if (!$this->storage->exists($logPath)) {
                throw new \Exception('Log file not found');
            }

            $content = $this->storage->get($logPath);

            return $this->success([
                'filename' => basename($logPath),
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Delete a site (Super Admin only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteSite(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || ($user->role ?? '') !== 'super_admin') {
                throw new \Exception('Unauthorized', 403);
            }

            $request->validate(['site_id' => 'required|integer']);
            $siteId = $request->input('site_id');

            $site = $this->mySiteRepository->findOrFail($siteId);

            $service = app(SiteDestructionService::class);
            $destroyResult = $service->destroy($site->path_source_code, $site->site_name);

            if ($destroyResult['success']) {
                $this->mySiteRepository->delete($siteId);
                return $this->success(['messages' => $destroyResult['messages']], 'Site deleted successfully');
            }

            return $this->error('Failed to delete site', 500, $destroyResult['messages']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
