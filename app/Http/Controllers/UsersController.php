<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UsersController extends Controller implements UpdatesUserProfileInformation
{
    public function users()
    {
        return User::all();
    }

    public function update_user(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
        ]);

        $user->update($request->only('name', 'email'));

        return response()->json(['message' => 'User updated successfully'], 200);
    }

    public function deleteUsers(Request $request)
    {
        $userIds = explode(',', $request->input('userIds'));

        User::whereIn('id', $userIds)->delete();

        return response()->json(['message' => 'Users deleted successfully'], 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('user-token', [
                'server:create', 'server:read', 'server:update', 'server:delete'
            ])->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user
            ], 200);
        }

        return response()->json([
            'message' => 'Authentication failed. Password or email error',
        ], 401);
    }

    // public function register(Request $request)
    // {
    //     $request->validate([
    //         'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'first_name' => 'required|string|max:255',
    //         'last_name' => 'required|string|max:255',
    //         'email' => 'required|email|max:255|unique:users',
    //         'phone' => 'required|string|max:20',
    //         'password' => 'required|string|min:8|confirmed',
    //     ]);

    //     if ($request->hasFile('photo')) {
    //         $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
    //         $profilePicturePath = $request->file('photo')->storeAs('public/profile-photo', $fileName);
    //         $databaseFilePath = 'profile-photo/' . $fileName;

    //         $user = User::create([
    //             'name' => $request->first_name,
    //             'last_name' => $request->last_name,
    //             'email' => $request->email,
    //             'phone' => $request->phone,
    //             'password' => Hash::make($request->password),
    //             'profile_photo_path' => $databaseFilePath,
    //         ]);

    //         if ($user) {
    //             $token = $user->createToken('user-token', [
    //                 'server:create', 'server:read', 'server:update', 'server:delete'
    //             ])->plainTextToken;

    //             return response()->json([
    //                 'token' => $token,
    //                 'user' => $user
    //             ], 200);
    //         }

    //         return response()->json(['message' => 'Registration failed'], 401);
    //     }

    //     return response()->json(['message' => 'No file'], 401);
    // }

    public function register(Request $request)
    {
        // Validate the request
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
           
        ]);
    
        // Check if the file exists before calling store()
        if ($request->hasFile('photo')) {
            // Generate a unique name for the file
            $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
    
            // Store the file in the public/profile-photo directory with the generated name
            $profilePicturePath = $request->file('photo')->storeAs('public/profile-photo', $fileName);
    
            // Save the file path in the database with a prefix
            $databaseFilePath = 'profile-photo/' . $fileName;
    
            // Create a new user instance and save it to the database
            $user = new User;
            $user->name = $request->first_name . ' ' . $request->last_name;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = Hash::make($request->password);
            $user->profile_photo_path = $databaseFilePath;
            
            if ($user->save()) {
                // Attempt to authenticate the user
                $credentials = $request->only('email', 'password');
                if (Auth::attempt($credentials)) {
                    $user = auth()->user();
                    $token = $user->createToken('user-token', ['server:create', 'server:read', 'server:update', 'server:delete'])->plainTextToken;
    
                    return response()->json([
                        'token' => $token,
                        'user' => $user
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Authentication failed',
                    ], 401);
                }
            } else {
                return response()->json([
                    'message' => 'Registration failed',
                ], 500);
            }
        } else {
            return response()->json([
                'message' => 'No file',
            ], 400);
        }
    }
    

    public function logout(Request $request)
    {
        $result = $request->user()->currentAccessToken()->delete();

        return $result
            ? response()->json(['message' => 'Logged out successfully'], 200)
            : response()->json(['message' => 'Logout failed'], 401);
    }
}
