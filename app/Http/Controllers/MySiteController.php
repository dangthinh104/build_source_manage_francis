<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MySite\BuildSiteRequest;
use App\Http\Requests\MySite\DeleteSiteRequest;
use App\Http\Requests\MySite\GetSiteDataRequest;
use App\Http\Requests\MySite\StoreSiteRequest;
use App\Http\Requests\MySite\UpdateSiteRequest;
use App\Http\Requests\MySite\ViewLogFileRequest;
use App\Jobs\ProcessSiteBuild;
use App\Repositories\Interfaces\BuildHistoryRepositoryInterface;
use App\Repositories\Interfaces\MySiteRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\MySiteStorageService;
use App\Services\SiteBuildService;
use App\Services\SiteDestructionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
     * @param StoreSiteRequest $request
     * @return RedirectResponse
     */
    public function store(StoreSiteRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->siteBuildService->createSite(
                $validated['site_name'],
                $validated['path_source_code'],
                $validated['include_pm2'] ?? false,
                $validated['port_pm2'] ?? null
            );

            return $this->redirectWithSuccess('my_site.index', 'Site created successfully!');
        } catch (\Exception $e) {
            return $this->redirectWithError('my_site.index', 'Failed to create site: ' . $e->getMessage());
        }
    }

    /**
     * Update site configuration
     *
     * @param UpdateSiteRequest $request
     * @return JsonResponse
     */
    public function update(UpdateSiteRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->mySiteRepository->update($validated['id'], [
                'site_name' => $validated['site_name'],
                'port_pm2' => $validated['port_pm2'] ?? null,
                'api_endpoint_url' => $validated['api_endpoint_url'] ?? null,
            ]);

            return $this->success(null, 'Site updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }

    /**
     * Queue a site build job
     *
     * @param BuildSiteRequest $request
     * @return JsonResponse
     */
    public function buildMySite(BuildSiteRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $siteId = $validated['site_id'];

            ProcessSiteBuild::dispatch($siteId, auth()->id());

            return $this->success([
                'status' => 'queued',
                'site_id' => $siteId,
            ], 'Build queued successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to queue build: ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Get the current build status for a site
     *
     * @param GetSiteDataRequest $request
     * @return JsonResponse
     */
    public function getBuildStatus(GetSiteDataRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $latestBuild = $this->buildHistoryRepository->getLatestBySiteId($validated['site_id']);

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
     * @param GetSiteDataRequest $request
     * @return JsonResponse
     */
    public function getLogLastBuildByID(GetSiteDataRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $data = $this->siteBuildService->getLogContent($validated['site_id']);

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get all site details including shell script and env content
     *
     * @param GetSiteDataRequest $request
     * @return JsonResponse
     */
    public function getAllDetailSiteByID(GetSiteDataRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $data = $this->siteBuildService->getSiteDetails($validated['site_id']);

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get build history for a specific site
     *
     * @param GetSiteDataRequest $request
     * @return JsonResponse
     */
    public function getBuildHistoryBySite(GetSiteDataRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $histories = $this->buildHistoryRepository->getFormattedBySiteId($validated['site_id']);

            return $this->success(['histories' => $histories]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get all log files for a site
     *
     * @param GetSiteDataRequest $request
     * @return JsonResponse
     */
    public function getSiteLogs(GetSiteDataRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $site = $this->mySiteRepository->findOrFail($validated['site_id']);

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
     * @param ViewLogFileRequest $request
     * @return JsonResponse
     */
    public function viewLogFile(ViewLogFileRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $site = $this->mySiteRepository->findOrFail($validated['site_id']);
            $logPath = $validated['log_path'];

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
     * Delete a site and all associated files
     *
     * @param DeleteSiteRequest $request
     * @return JsonResponse
     */
    public function deleteSite(DeleteSiteRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $site = $this->mySiteRepository->findOrFail($validated['site_id']);

            $service = app(SiteDestructionService::class);
            $destroyResult = $service->destroy($site->path_source_code, $site->site_name);

            if ($destroyResult['success']) {
                $this->mySiteRepository->delete($site->id);
                return $this->success(['messages' => $destroyResult['messages']], 'Site deleted successfully');
            }

            return $this->error('Failed to delete site', 500, $destroyResult['messages']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
