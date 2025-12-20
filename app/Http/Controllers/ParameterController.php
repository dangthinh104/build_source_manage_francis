<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    public function __construct()
    {
        $user = auth()->user();
        if (!$user || $user->role !== User::ROLE_SUPER_ADMIN) {
            abort(403, 'Unauthorized');
        }
    }
    public function index(Request $request)
    {
        $query = Parameter::query();

        if ($request->has('key')) {
            $query->where('key', 'like', '%' . $request->input('key') . '%');
        }

        $parameters = $query
            ->orderBy('key')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($p) {
                return [
                    'id' => $p->id,
                    'key' => $p->key,
                    'value' => $p->value,
                    'type' => $p->type,
                    'description' => $p->description,
                ];
            });

        return \Inertia\Inertia::render('Parameters/Index', [
            'parameters' => $parameters,
            'filters' => $request->only(['key']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|max:255|unique:parameters,key',
            'value' => 'required|string',
            'type' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $parameter = Parameter::create($data);

        return response()->json($parameter, 201);
    }

    public function update(Request $request, Parameter $parameter)
    {
        $data = $request->validate([
            'key' => 'required|string|max:255|unique:parameters,key,'.$parameter->id,
            'value' => 'required|string',
            'type' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $parameter->update($data);

        return response()->json($parameter);
    }

    public function destroy(Parameter $parameter)
    {
        $parameter->delete();

        return response()->json([
            'message' => 'Parameter deleted successfully',
        ]);
    }
}
