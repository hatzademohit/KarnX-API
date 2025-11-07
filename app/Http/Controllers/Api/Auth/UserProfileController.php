<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserProfileController extends Controller
{
    /**
     * Get the logged-in user's profile
     */
    public function index()
    {
        $user = Auth::user();

        return response()->json([
            'status' => true,
            'data' => $user
        ]);
    }

    /**
     * Update the logged-in user's profile
     */
    public function store(Request $request)
    {
        try{
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name'        => 'required|string|max:255',
                'email'       => 'required|email|max:255|unique:users,email,' . $user->id,
                'phone'      => 'required|string|min:0|max:11|unique:users,phone,' . $user->id,
                'dob'         => 'required|date',
                'gender'      => 'required|in:male,female,other',
                'avatar'      => 'nullable|image|max:2048', // for profile picture
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle profile picture upload
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }

            // Update other profile details
            $user->name   = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->dob    = Carbon::parse($request->dob)->format('Y-m-d');
            $user->gender = $request->gender;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try{
            // 1. Validate input
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password'     => 'required|string|min:8|confirmed', // requires new_password_confirmation
                'new_password_confirmation' => 'required|string|min:8',
            ]);

            
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $user = Auth::user();
           
            // 2. Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'errors' => 'The current password is incorrect.',
                ], 422);
            }

            // 3. Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'status'  => true,
                'message' => 'Password changed successfully.',
            ], 200);
        }catch(Exception $e){
            return response()->json([
                'status'  => false,
                'message' => 'Failed to change password.',
            ], 500);
        }    
    }
}
