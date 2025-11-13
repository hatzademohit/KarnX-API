<?php

namespace App\Http\Controllers\Api\Configure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Module;
class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $modules = Module::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Modules retrieved successfully',
            'data' => $modules,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:100',
                'description' => 'nullable|string',
            ]);
            $module = Module::create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Module created successfully',
                'data' => $module,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $module = Module::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Module retrieved successfully',
                'data' => $module,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $module = Module::findOrFail($id);
            $module->update($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Module updated successfully',
                'data' => $module,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $module = Module::findOrFail($id);
            $module->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Module deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
