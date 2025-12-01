<?php
namespace App\Http\Controllers;

use App\Models\EnvVariable;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnvVariableController extends Controller
{
    public function __construct()
    {
        // Only admin and super_admin can manage environment variables
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Forbidden. Only Admin or Super Admin can manage environment variables.');
        }
    }

    public function index()
    {

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
        $request->validate([
            'variable_name' => 'required|string|max:255',
            'variable_value' => 'required|string',
        ]);

        $encryptedValue = encryptValue($request->input('variable_value'));

        $data = EnvVariable::create([
            'variable_name' => $request->input('variable_name'),
            'variable_value' => $encryptedValue,
        ]);

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $envVariable = EnvVariable::findOrFail($id);

        $request->validate([
            'variable_value' => 'required|string',
        ]);

        $encryptedValue = encryptValue($request->input('variable_value'));

        $envVariable->update([
            'variable_value' => $encryptedValue,
        ]);
        return response()->json($envVariable);
    }

    public function destroy($id)
    {
        $envVariable = EnvVariable::findOrFail($id);
        $envVariable->delete();
        return response()->json($envVariable);
    }

}

