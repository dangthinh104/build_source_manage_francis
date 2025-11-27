<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    public function index()
    {
        $parameters = Parameter::query()
            ->orderBy('key')
            ->get();

        return response()->json($parameters);
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
