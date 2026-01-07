<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BuildGroup\BuildGroupRequest;
use App\Http\Requests\BuildGroup\StoreBuildGroupRequest;
use App\Http\Requests\BuildGroup\UpdateBuildGroupRequest;
use App\Jobs\ProcessSiteBuild;
use App\Repositories\Interfaces\BuildGroupRepositoryInterface;
use App\Repositories\Interfaces\MySiteRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * BuildGroup Controller
 * 
 * Handles HTTP requests for build group management.
 * Delegates data operations to BuildGroupRepository.
 * Uses Form Requests for validation.
 */
class BuildGroupController extends BaseController
{
    /**
     * Constructor - Inject dependencies
     *
     * @param BuildGroupRepositoryInterface $buildGroupRepository
     * @param MySiteRepositoryInterface $mySiteRepository
     */
    public function __construct(
        protected BuildGroupRepositoryInterface $buildGroupRepository,
        protected MySiteRepositoryInterface $mySiteRepository
    ) {}

    /**
     * Display a paginated list of build groups
     *
     * @return Response
     */
    public function index(): Response
    {
        $groups = $this->buildGroupRepository->getGroupsWithSitesAndUser(15);
        $allSites = $this->mySiteRepository->all(['id', 'site_name'])->sortBy('site_name');

        return inertia('BuildGroups/Index', [
            'groups' => $groups,
            'allSites' => $allSites->values(),
        ]);
    }

    /**
     * Store a newly created build group
     *
     * @param StoreBuildGroupRequest $request
     * @return RedirectResponse
     */
    public function store(StoreBuildGroupRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $group = $this->buildGroupRepository->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'user_id' => auth()->id(),
            ]);

            if (isset($validated['site_ids']) && !empty($validated['site_ids'])) {
                $this->buildGroupRepository->syncSites($group->id, $validated['site_ids']);
            }

            return $this->redirectWithSuccess('build_groups.index', 'Build Group created successfully');
        } catch (\Exception $e) {
            return $this->redirectWithError('build_groups.index', 'Failed to create build group: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified build group
     *
     * @param UpdateBuildGroupRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(UpdateBuildGroupRequest $request, int $id): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->buildGroupRepository->update($id, [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            if (isset($validated['site_ids'])) {
                $this->buildGroupRepository->syncSites($id, $validated['site_ids']);
            }

            return $this->redirectWithSuccess('build_groups.index', 'Build Group updated successfully');
        } catch (\Exception $e) {
            return $this->redirectWithError('build_groups.index', 'Failed to update build group: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified build group
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->buildGroupRepository->delete($id);
            return $this->redirectWithSuccess('build_groups.index', 'Build Group deleted successfully');
        } catch (\Exception $e) {
            return $this->redirectWithError('build_groups.index', 'Failed to delete build group: ' . $e->getMessage());
        }
    }

    /**
     * Trigger build for all sites in the group
     *
     * @param BuildGroupRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function build(BuildGroupRequest $request, int $id): RedirectResponse
    {
        try {
            $group = $this->buildGroupRepository->getGroupWithSites($id);

            if ($group->sites->isEmpty()) {
                return $this->redirectWithError('build_groups.index', 'No sites in this group to build');
            }

            $triggeredCount = 0;
            foreach ($group->sites as $site) {
                ProcessSiteBuild::dispatch($site->id, auth()->id());
                $triggeredCount++;
            }

            return $this->redirectWithSuccess('build_groups.index', "Build queued for {$triggeredCount} sites");
        } catch (\Exception $e) {
            return $this->redirectWithError('build_groups.index', 'Failed to trigger build: ' . $e->getMessage());
        }
    }
}
