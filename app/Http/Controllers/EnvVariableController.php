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
    public function index(Request $request): Response
    {
        $this->checkAdminAccess();

        $query = EnvVariable::with('mySite');

        if ($request->has('variable_name')) {
            $query->where('variable_name', 'like', '%' . $request->input('variable_name') . '%');
        }

        $envVariables = $query->paginate(10)->withQueryString();
        
        $envVariables->through(function ($variable) {
            return [
                'id' => $variable->id,
                'variable_name' => $variable->variable_name,
                'variable_value' => decryptValue($variable->variable_value),
                'group_name' => $variable->group_name,
                'my_site_id' => $variable->my_site_id,
                'site_name' => $variable->mySite?->site_name,
            ];
        });

        return Inertia::render('EnvVariables/Index', [
            'envVariables' => $envVariables,
            'filters' => $request->only(['variable_name']),
            'sites' => \App\Models\MySite::orderBy('site_name')->get(['id', 'site_name']),
        ]);
    }

    /**
     * Store a new environment variable.
     */
    public function store(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'variable_name' => 'required|string|max:255',
            'variable_value' => 'required|string',
            'group_name' => 'nullable|string|max:255',
            'my_site_id' => 'nullable|integer|exists:my_site,id',
        ]);

        // Business logic: group_name and my_site_id are mutually exclusive
        if (!empty($validated['group_name']) && !empty($validated['my_site_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'A variable cannot be both group-scoped and site-specific. Please choose one or leave both empty for a global variable.',
            ], 422);
        }

        // Check for duplicate in the same scope
        $existingVar = EnvVariable::where('variable_name', $validated['variable_name'])
            ->where('group_name', $validated['group_name'] ?? null)
            ->where('my_site_id', $validated['my_site_id'] ?? null)
            ->first();

        if ($existingVar) {
            return response()->json([
                'success' => false,
                'message' => 'A variable with this name already exists in the same scope.',
            ], 422);
        }

        $data = EnvVariable::create([
            'variable_name' => $validated['variable_name'],
            'variable_value' => encryptValue($validated['variable_value']),
            'group_name' => $validated['group_name'] ?? null,
            'my_site_id' => $validated['my_site_id'] ?? null,
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
            'group_name' => 'nullable|string|max:255',
            'my_site_id' => 'nullable|integer|exists:my_site,id',
        ]);

        // Business logic: group_name and my_site_id are mutually exclusive
        if (!empty($validated['group_name']) && !empty($validated['my_site_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'A variable cannot be both group-scoped and site-specific. Please choose one or leave both empty for a global variable.',
            ], 422);
        }

        $envVariable->update([
            'variable_value' => encryptValue($validated['variable_value']),
            'group_name' => $validated['group_name'] ?? null,
            'my_site_id' => $validated['my_site_id'] ?? null,
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
