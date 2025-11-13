<?php

namespace App\Http\Controllers\Api\Configure;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class PermissionHasAccessController extends Controller
{
    // Assign permissions to a role
    public function assignPermissionsToRole(Request $request, $roleId)
    {

        $role = Role::findOrFail($roleId);
        $role->syncPermissions($request->permissions); // expects array of permission names or IDs
        return response()->json([
            'status' => true,
            'message' => 'Permissions assigned to role successfully',
            'data' => $request->permissions,
            'role' => $role,
        ], 200);
    }

    // Get permissions for a role
    public function getRolePermissions($roleId)
    {
        $role = Role::findOrFail($roleId);
        return response()->json([
            'status' => true,
            'data' => $role->permissions,
        ], 200);
    }

    // Check if role has specific permission
    public function roleHasPermission($roleId, $permission)
    {
        $role = Role::findOrFail($roleId);
        return response()->json([
            'status' => true,
            'data' => $role->hasPermissionTo($permission),
        ], 200);
    }

    // Assign permissions directly to a user
    public function assignPermissionsToUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $user->syncPermissions($request->permissions); // expects array of permission names or IDs
        return response()->json([
            'status' => true,
            'message' => 'Permissions assigned to user successfully',
        ], 200);
    }

    // Get permissions for a user (including from roles)
    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);
        return response()->json([
            'status' => true,
            'data' => $user->getAllPermissions(),
        ], 200);
    }

    // Check if user has a specific permission
    public function userHasPermission($userId, $permission)
    {
        $user = User::findOrFail($userId);
        return response()->json([
            'status' => true,
            'data' => $user->hasPermissionTo($permission),
        ], 200);
    }
}
