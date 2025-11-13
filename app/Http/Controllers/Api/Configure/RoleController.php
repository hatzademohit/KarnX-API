<?php

namespace App\Http\Controllers\Api\Configure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'client_id' => 'required',
            ]);
            if($request->client_id == 1){
                $roles = Role::join('clients', 'roles.client_id', '=', 'clients.id')->select('roles.*', 'clients.name as client_name')->get();
            }else{
                $roles = Role::join('clients', 'roles.client_id', '=', 'clients.id')->where('client_id', $request->client_id)->select('roles.*', 'clients.name as client_name')->get();
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Roles retrieved successfully',
                'data' => $roles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve roles',
                'error' => $e->getMessage(),
            ], 500);
        }   
    }

    public function show($role)
    {
        try {
            $roles = Role::findOrFail($role);
            return response()->json([
                'status' => 'success',
                'message' => 'Role retrieved successfully',
                'data' => $roles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve role',
                'error' => $e->getMessage(),
            ], 500);
        }   
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('roles')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->client_id);
                })],
                'description' => 'nullable|string',
                'client_id' => 'required|exists:clients,id',
            ]);
            $roles = Role::create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Role created successfully',
                'data' => $roles,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create role',
                'error' => $e->getMessage(),
            ], 500);
        }    
    }

    public function update(Request $request, $role)
    {
        try {
            $rules = [];
            if ($request->has('name')) {
                $rules['name'] = ['required', 'string', 'max:255', Rule::unique('roles')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->client_id);
                })->ignore($role)];
            }
            if ($request->has('description')) {
                $rules['description'] = 'nullable|string';
            }
            if ($request->has('client_id')) {
                $rules['client_id'] = 'required';
            }
            $request->validate($rules);
            $roles = Role::findOrFail($role);
            $roles->update($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => $roles,
        ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update role',
                'error' => $e->getMessage(),
            ], 500);
        }    
    }

    public function destroy($role)
    {
        try {
            $roles = Role::findOrFail($role);
            $roles->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Role deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete role',
                'error' => $e->getMessage(),
            ], 500);
        }    
    }   
}
