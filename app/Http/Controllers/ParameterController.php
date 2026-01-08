<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Parameter\StoreParameterRequest;
use App\Http\Requests\Parameter\UpdateParameterRequest;
use App\Http\Requests\Parameter\DeleteParameterRequest;
use App\Repositories\Interfaces\ParameterRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Controller for managing application parameters.
 * 
 * Uses Repository Pattern and Form Requests for clean architecture.
 */
class ParameterController extends BaseController
{
    /**
     * Constructor - Inject dependencies
     *
     * @param ParameterRepositoryInterface $parameterRepository
     */
    public function __construct(
        protected ParameterRepositoryInterface $parameterRepository
    ) {}

    /**
     * Display list of parameters.
     */
    public function index(Request $request): Response
    {
        $parameters = $this->parameterRepository->getPaginatedWithFilters(
            $request->input('key'),
            10
        );

        return inertia('Parameters/Index', [
            'parameters' => $parameters,
            'filters' => $request->only(['key']),
        ]);
    }

    /**
     * Store a new parameter.
     */
    public function store(StoreParameterRequest $request): JsonResponse
    {
        try {
            $parameter = $this->parameterRepository->create($request->validated());

            return $this->success($parameter, 'Parameter created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }

    /**
     * Update an existing parameter.
     */
    public function update(UpdateParameterRequest $request, int $id): JsonResponse
    {
        try {
            $this->parameterRepository->update($id, $request->validated());

            return $this->success(null, 'Parameter updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }

    /**
     * Delete a parameter.
     */
    public function destroy(DeleteParameterRequest $request, int $id): JsonResponse
    {
        try {
            $this->parameterRepository->delete($id);

            return $this->success(null, 'Parameter deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }
}
