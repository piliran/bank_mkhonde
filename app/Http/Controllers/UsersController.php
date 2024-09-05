<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AccountType;
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
    // Validate login credentials
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        
        // Retrieve the account type associated with the user
        $account = AccountType::where('user_id', $user->id)->get();
        
        // Generate a token for the user
        $token = $user->createToken('user-token', [
            'server:create', 'server:read', 'server:update', 'server:delete'
        ])->plainTextToken;

        // Return the user data, token, and account type
        return response()->json([
            'token' => $token,
            'user' => $user,
            'account' => $account ? $account: null,  // Return account type or null if not found
        ], 200);
    }

    // Return authentication failure message
    return response()->json([
        'message' => 'Authentication failed. Password or email error',
    ], 401);
}


   

    public function register(Request $request)
    {
        // Check if the file exists before calling store()
        $databaseFilePath = null;
    
        if ($request->hasFile('photo')) {
            // Generate a unique name for the file
            $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
    
            // Store the file in the public/profile-photo directory with the generated name
            $profilePicturePath = $request->file('photo')->storeAs('public/profile-photo', $fileName);
    
            // Save the file path in the database with a prefix
            $databaseFilePath = 'profile-photo/' . $fileName;
        }
    
        // Create a new user instance and save it to the database
        $user = new User;
        $user->name = $request->first_name . ' ' . $request->last_name;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->account_type = $request->account_type;
        $user->student_id = $request->student_id;
        $user->national_id = $request->national_id;
        $user->traditional_authority = $request->traditional_authority;
        $user->home_village = $request->home_village;
        $user->occupation = $request->occupation;
        $user->phone = $request->phone;
        $user->airtel_money_number = $request->airtel_money_number;
        $user->mpamba_number = $request->mpamba_number;
        $user->home_physical_address = $request->home_physical_address;
        $user->physical_address = $request->physical_address;
        $user->current_physical_address = $request->current_physical_address;
        $user->guardian = $request->guardian;
        $user->bank_name = $request->bank_name;
        $user->account_number = $request->account_number;
        $user->branch = $request->branch;
        $user->monthly_income = $request->monthly_income;
        $user->company_name = $request->company_name;
        $user->lending_limit = $request->lending_limit;
        $user->collateral_required = $request->collateral_required;
        $user->preferred_borrower_criteria = $request->preferredBorrowerCriteria;
        $user->company_annual_revenue = $request->company_annual_revenue;
        $user->business_registration_number = $request->business_registration_number;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
    
        // Only set the profile_photo_path if a file was uploaded
        if ($databaseFilePath) {
            $user->profile_photo_path = $databaseFilePath;
        }
    
        // Save the user and handle authentication 
        if ($user->save()) {
           
            // Attempt to authenticate the user
            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {
                $user = auth()->user();
                $account= AccountType::create([
                    'user_id' => $user->id,
                    'type' => $request->account_type,               
                ]);

                
                $token = $user->createToken('user-token', ['server:create', 'server:read', 'server:update', 'server:delete'])->plainTextToken;
    
                return response()->json([
                    'token' => $token,
                    'user' => $user,
                    'account' => $account,
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
    }
    
    

    public function logout(Request $request)
    {
        $result = $request->user()->currentAccessToken()->delete();

        return $result
            ? response()->json(['message' => 'Logged out successfully'], 200)
            : response()->json(['message' => 'Logout failed'], 401);
    }
}
