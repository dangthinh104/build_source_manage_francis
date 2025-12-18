<?php
namespace App\Http\Controllers;

use App\Models\EnvVariable;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnvVariableController extends Controller
{
    /**
     * Check if current user has admin privileges.
     * Aborts with 403 if not authorized.
     */
    private function checkAdminAccess(): void
    {
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Forbidden. Only Admin or Super Admin can manage environment variables.');
        }
    }

    public function index()
    {
        $this->checkAdminAccess();

        $envVariables = EnvVariable::all()->map(function ($variable) {
            return [
                'id' => $variable->id,
                'variable_name' => $variable->variable_name,
                'variable_value' =>decryptValue($variable->variable_value),
            ];
        });

        // Render the Inertia view and pass the environment variables data
        return Inertia::render('EnvVariables/Index', [
            'envVariables' => $envVariables,
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAdminAccess();

        $request->validate([
            'variable_name' => 'required|string|max:255',
            'variable_value' => 'required|string',
        ]);

        $encryptedValue = encryptValue($request->input('variable_value'));

        $data = EnvVariable::create([
            'variable_name' => $request->input('variable_name'),
            'variable_value' => $encryptedValue,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Variable created successfully',
            'data' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->checkAdminAccess();

        $envVariable = EnvVariable::findOrFail($id);

        $request->validate([
            'variable_value' => 'required|string',
        ]);

        $encryptedValue = encryptValue($request->input('variable_value'));

        $envVariable->update([
            'variable_value' => $encryptedValue,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Variable updated successfully',
            'data' => $envVariable,
        ]);
    }

    public function destroy($id)
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
