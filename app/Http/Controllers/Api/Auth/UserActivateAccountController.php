<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserActivateAccountController extends Controller
{
    public function activateAccount(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'name' => 'required|string|max:255',
                'password' => 'required|string|min:8',
                'confirm_password' => 'required|string|min:8|same:password',
                'phone' => 'required|numeric|digits:10',
                'dob' => 'required|date',
                'gender' => 'required|in:male,female,other',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->activation_token !== $request->token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid activation token',
                ], 400);
            }
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->activation_token = null;
            $user->is_active = 1;
            $user->name = $request->name;
            $user->password = Hash::make($request->password);
            $user->phone = $request->phone;
            $user->dob = Carbon::parse($request->dob)->format('Y-m-d');
            $user->gender = $request->gender;
            $user->email_verified_at = now();
            $user->remember_token = $token;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Account activated successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
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
