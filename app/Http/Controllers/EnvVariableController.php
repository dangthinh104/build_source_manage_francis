<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EnvVariable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing environment variables.
 * 
 * Security: Route-level middleware ensures only Admin/Super Admin can access.
 * Data is encrypted using encryptValue/decryptValue helpers from MyHelper.php.
 */
class EnvVariableController extends Controller
{
    /**
     * Check if current user has admin privileges.
     * Aborts with 403 if not authorized.
     * 
     * Note: This is defense-in-depth. Routes are also protected by RoleMiddleware.
     */
    private function checkAdminAccess(): void
    {
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Forbidden. Only Admin or Super Admin can manage environment variables.');
        }
    }

    /**
     * Display list of environment variables.
     */
    public function index(): Response
    {
        $this->checkAdminAccess();

        $envVariables = EnvVariable::all()->map(function ($variable) {
            return [
                'id' => $variable->id,
                'variable_name' => $variable->variable_name,
                'variable_value' => decryptValue($variable->variable_value),
            ];
        });

        return Inertia::render('EnvVariables/Index', [
            'envVariables' => $envVariables,
        ]);
    }

    /**
     * Store a new environment variable.
     */
    public function store(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'variable_name' => 'required|string|max:255|unique:env_variables,variable_name',
            'variable_value' => 'required|string',
        ]);

        $data = EnvVariable::create([
            'variable_name' => $validated['variable_name'],
            'variable_value' => encryptValue($validated['variable_value']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Variable created successfully',
            'data' => $data,
        ]);
    }

    /**
     * Update an existing environment variable.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkAdminAccess();

        $envVariable = EnvVariable::findOrFail($id);

        $validated = $request->validate([
            'variable_value' => 'required|string',
        ]);

        $envVariable->update([
            'variable_value' => encryptValue($validated['variable_value']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Variable updated successfully',
            'data' => $envVariable,
        ]);
    }

    /**
     * Delete an environment variable.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->checkAdminAccess();

        $envVariable = EnvVariable::findOrFail($id);
        $envVariable->delete();

        return response()->json([
            'success' => true,
            'message' => 'Variable deleted successfully',
        ]);
    }
}
