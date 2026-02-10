<?php

namespace App\Http\Controllers\Api;

use App\Models\Road;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RoadController extends Controller
{
    public function index()
    {
        $roads = Road::all();
        return response()->json($roads);
    }

    public function show($id)
    {
        $road = Road::findOrFail($id);
        return response()->json($road);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'area' => 'required|numeric',
        ]);

        $road = Road::create($validated);
        return response()->json($road, 201);
    }

    public function update(Request $request, $id)
    {
        $road = Road::findOrFail($id);
        $validated = $request->validate([
            'designation' => 'string',
            'longitude' => 'numeric',
            'latitude' => 'numeric',
            'area' => 'numeric',
        ]);

        $road->update($validated);
        return response()->json($road);
    }

    public function destroy($id)
    {
        $road = Road::findOrFail($id);
        $road->delete();
        return response()->json(['message' => 'Route supprimée']);
    }
}