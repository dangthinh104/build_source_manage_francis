<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EnvVariable\StoreEnvVariableRequest;
use App\Http\Requests\EnvVariable\UpdateEnvVariableRequest;
use App\Http\Requests\EnvVariable\DeleteEnvVariableRequest;
use App\Repositories\Interfaces\EnvVariableRepositoryInterface;
use App\Repositories\Interfaces\MySiteRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Controller for managing environment variables.
 * 
 * Uses Repository Pattern and Form Requests for clean architecture.
 * Values are automatically encrypted/decrypted via model accessors.
 */
class EnvVariableController extends BaseController
{
    /**
     * Constructor - Inject dependencies
     *
     * @param EnvVariableRepositoryInterface $envVariableRepository
     * @param MySiteRepositoryInterface $mySiteRepository
     */
    public function __construct(
        protected EnvVariableRepositoryInterface $envVariableRepository,
        protected MySiteRepositoryInterface $mySiteRepository
    ) {}

    /**
     * Display list of environment variables.
     */
    public function index(Request $request): Response
    {
        $envVariables = $this->envVariableRepository->getPaginatedWithFilters(
            $request->input('variable_name'),
            10
        );

        // Transform data to decrypt values for display
        $envVariables->through(function ($variable){
            return [
                'id' => $variable->id,
                'variable_name' => $variable->variable_name,
                'variable_value' => decryptValue($variable->variable_value),
                'group_name' => $variable->group_name,
                'my_site_id' => $variable->my_site_id,
                'site_name' => $variable->mySite?->site_name,
            ];
        });

        $sites = $this->mySiteRepository->all(['id', 'site_name']);

        return inertia('EnvVariables/Index', [
            'envVariables' => $envVariables,
            'filters' => $request->only(['variable_name']),
            'sites' => $sites,
        ]);
    }

    /**
     * Store a new environment variable.
     */
    public function store(StoreEnvVariableRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->envVariableRepository->create([
                'variable_name' => $validated['variable_name'],
                'variable_value' => encryptValue($validated['variable_value']),
                'group_name' => $validated['group_name'] ?? null,
                'my_site_id' => $validated['my_site_id'] ?? null,
            ]);

            return $this->success(null, 'Variable created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }

    /**
     * Update an existing environment variable.
     */
    public function update(UpdateEnvVariableRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->envVariableRepository->update($id, [
                'variable_value' => encryptValue($validated['variable_value']),
                'group_name' => $validated['group_name'] ?? null,
                'my_site_id' => $validated['my_site_id'] ?? null,
            ]);

            return $this->success(null, 'Variable updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }

    /**
     * Delete an environment variable.
     */
    public function destroy(DeleteEnvVariableRequest $request, int $id): JsonResponse
    {
        try {
            $this->envVariableRepository->delete($id);

            return $this->success(null, 'Variable deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }
}
