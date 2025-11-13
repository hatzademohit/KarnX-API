<?php

namespace App\Http\Controllers\Api\Configure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if($request->has('by') == 'module'){
            $permissions = Permission::select('id', 'slug', 'name')
                            ->get()
                            ->groupBy('slug')
                            ->map(function ($group, $slug) {
                                return [
                                    'slug' => $slug,
                                    'permissions' => $group->map(function ($item) {
                                        return [
                                            'id' => $item->id,
                                            'name' => $item->name,
                                        ];
                                    })->sortBy('name')->values()->toArray(),
                                ];
                            })
                            ->values();
        }else{
            $permissions = Permission::all();
        }
        return response()->json([
            'status' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => $permissions,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
        ]);
        $permission = Permission::create($request->all());
        return response()->json([
            'status' => true,
            'message' => 'Permission created successfully',
            'permission' => $permission,
        ], 200);
    }   

    public function show(Permission $permission)
    {
        return response()->json([
            'status' => true,
            'message' => 'Permission retrieved successfully',
            'permission' => $permission,
        ], 200);
    }

    public function update(Request $request, Permission $permission)
    {
       
        $rules = [];
        if ($request->has('name')) {
            $rules['name'] = 'required|string|max:255|unique:permissions,name,' . $permission->id;
        }
        if ($request->has('guard_name')) {
            $rules['guard_name'] = 'required|string|max:255';
        }
        if ($request->has('slug')) {
            $rules['slug'] = 'required|string|max:255';
        }
        $request->validate($rules);
        $permission->update($request->only(['name', 'guard_name', 'slug']));

        return response()->json([
            'status' => true,
            'message' => 'Permission updated successfully',
            'permission' => $permission,
        ], 200);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json([
            'status' => true,
            'message' => 'Permission deleted successfully',
            'permission' => $permission,
        ], 200);
    }   
}
