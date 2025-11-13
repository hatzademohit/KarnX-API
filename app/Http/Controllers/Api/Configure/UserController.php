<?php

namespace App\Http\Controllers\Api\Configure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\ActivateAccountNotification;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'client_id' => 'required',
                'search'    => 'nullable|string',
                'sortBy'    => 'nullable|string|in:id,name,email,created_at', // whitelist sortable columns
                'order'     => 'nullable|in:asc,desc',
                'page'      => 'nullable|integer|min:1',
                'limit'     => 'nullable|integer|min:1|max:100',
            ]);
    
            // Defaults
            $sortBy = $request->get('sortBy', 'id');
            $order  = $request->get('order', 'asc');
            $limit  = (int)$request->get('limit', 10);
            $page   = (int)$request->get('page', 1);
            $query = User::join('clients', 'users.client_id', '=', 'clients.id')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->select('users.*', 'clients.name as client_name', 'roles.name as role');
    
            // Client filter
            if ($request->client_id != 1) {
                $query->where('users.client_id', $request->client_id);
            }
    
            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'LIKE', "%$search%")
                        ->orWhere('users.email', 'LIKE', "%$search%")
                        ->orWhere('clients.name', 'LIKE', "%$search%")
                        ->orWhere('roles.name', 'LIKE', "%$search%");
                });
            }
    
            // Sorting
            $query->orderBy($sortBy, $order);
    
            // Pagination
            $users = $query->paginate($limit, ['*'], 'page', $page);
            return response()->json([
                'status' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage(),
            ], 500);
        }   
    }

    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            return response()->json([
                'status' => true,
                'message' => 'User retrieved successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage(),
            ], 500);
        }   
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                //'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users',
                //'password' => 'required|string|min:8',
                'client_id' => 'required',
                'role_id' => 'required',
            ]);
            $request->merge([
                'activation_token' => Str::random(60),

            ]);
            
            $users = User::create($request->all());
            if (method_exists($users, 'assignRole')) {
                $role = Role::where('id', $request->role_id)
                ->where('guard_name', 'api') // match your guard
                ->first();
                if ($role) {
                    $users->assignRole($role); // Pass Role model
                }
            }

            $users->notify(new ActivateAccountNotification($users));
    
            return response()->json([
                'status' => true,
                'message' => 'User registered and activation email sent successfully',
                'data' => $users,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }   
    }   

    public function update(Request $request, $id)
    {
        
        try {
            $rules = [];
            if ($request->has('client_id')) {
                $rules['client_id'] = 'required|exists:clients,id';
            }
            if ($request->has('role_id')) {
                $rules['role_id'] = 'required|exists:roles,id';
            }
            $request->validate($rules);

            $user = User::find($id);
            $user->update($request->all());
                
            return response()->json([
                'status'  => true,
                'message' => 'User updated successfully',
                'data'    => $user,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }   
    }   

    public function destroy($id)
    {
        try {
            $user = User::find($id);
            $user->delete();
            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }   
    }   
}
