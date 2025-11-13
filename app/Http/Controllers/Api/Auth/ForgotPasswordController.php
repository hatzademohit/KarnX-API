<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        
        try {
            $request->validate([
                'email' => 'required|email',
            ]);
            
            $user = User::where(['email' => $request->email])->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }
            
            $userCond2 = User::where(['email' => $request->email, 'is_active' => 1])->first();

            if (!$userCond2) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not active',
                ], 404);
            }
            
            $status = Password::sendResetLink(
                $request->only('email')
            );
            
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'status' => true,
                    'message' => 'Password reset link sent to your email.',
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'Unable to send reset link.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to send reset link.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);
            
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user) use ($request) {
                    $user->forceFill([
                        'password' => Hash::make($request->password),
                        'remember_token' => Str::random(60),
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status' => true,
                    'message' => 'Password reset successful.',
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'Invalid token.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
