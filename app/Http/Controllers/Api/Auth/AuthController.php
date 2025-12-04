<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Client;

class AuthController extends Controller
{
   
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'message' => 'The provided credentials are incorrect.',
                ]);
            }

            $user = Auth::user();
            $accessType = Client::where('id', $user->client_id)->first()->type;

            $user->access_type = $accessType??'';

            // Delete all previous tokens
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
           
            //$user = $user->load('roles', 'permissions'); // eager load
            
            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
                'role' => $user->getRoleNames()->first() ?? '',
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    // public function register(Request $request)
    // {
    //     try {
    //         // Step 1: Validate input
    //         $request->validate([
    //             'name'     => 'required|string|max:255',
    //             'email'    => 'required|string|email|max:255|unique:users',
    //             'password' => 'required|string|min:6',
    //             'phone' => 'required|digits:10|unique:users',
    //         ]);
            
    //         // Step 2: Create user
    //         $user = User::create([
    //             'name'     => $request->name,
    //             'email'    => $request->email,
    //             'password' => Hash::make($request->password),
    //             'phone'    => $request->phone,

    //         ]);

    //         // Step 3: Create token
    //         $token = $user->createToken('auth_token')->plainTextToken;

    //         // Step 4: Return response
    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'User registered successfully',
    //             'data'    => [
    //                 'user'  => $user,
    //                 'token' => $token
    //             ]
    //         ], 201);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors(),
    //         ], 422);

    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' => 'An error occurred during registration',
    //             'error' => $th->getMessage(),
    //         ], 500);
    //     }
    // }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        activity()
        ->performedOn($request->user())
        ->causedBy($request->user())
        ->withProperties([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ])
        ->log('User logged out');
        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
        ],200);
    }

    public function checkToken(Request $request)
    {
        return response()->json([
            'valid' => true,
            'user' => $request->user()
        ]);
    }
}
