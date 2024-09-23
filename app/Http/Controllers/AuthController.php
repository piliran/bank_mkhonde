<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

use App\Models\AccountType;
use App\Models\Bank;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\AccountTypeResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\PersonalAccessTokenResult;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = new User();

        // Assign values field by field
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password); // Hash the password
      
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
        $user->lending_minimum = $request->lending_minimum;
        $user->interest_rate = $request->interest_rate;
        $user->collateral_required = $request->collateral_required;
        $user->preferred_borrower_criteria = $request->preferred_borrower_criteria;
        $user->company_annual_revenue = $request->company_annual_revenue;
        $user->business_registration_number = $request->business_registration_number;
        $user->profile_photo_path = $request->profile_photo_path;
        $user->terms_and_conditions = $request->terms_and_conditions ? true : false;
    
        // Save the user to the database
        $user->save();
    
        // Store bank information, ensuring that $request->banks is present
        if ($request->has('banks')) {
            foreach ($request->banks as $bankData) {
                Bank::create(array_merge($bankData, ['user_id' => $user->id]));
            }
        }
    
        // Store account type if present
        if ($request->account_type) {
            AccountType::firstOrCreate(['user_id' => $user->id, 'type' => $request->account_type]);
        }
    
        // Generate access token for the newly registered user
        // $tokenResult = $user->createToken('Personal Access Token')->accessToken;
        $token = $user->createToken('user-token')->plainTextToken;

    
        // Return the registered user data, including account type and token
        return response()->json([
            'user' => new UserResource($user),
            'account' => new AccountTypeResource($user->accountType),
            'token' => $token,
            // 'access_token' => $tokenResult,
        ], 201);
    }
    

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
    
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            // $tokenResult = $user->createToken('Personal Access Token')->accessToken;
            $token = $user->createToken('user-token')->plainTextToken;

    
            return response()->json([
                'token' => $token,
                'user' => new UserResource($user),
                'account' => new AccountTypeResource($user->accountType),
            

            ], 200);
        }
    
        return response()->json(['error' => 'Invalid email or password.'], 401);
    }
    

    public function logout(): JsonResponse
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);

        // $save=   $request->user()->currentAccessToken()->delete();
     

        //  if ($save) {
        //     return response()->json([
        //         'message' => 'Logged out success',
        //     ], 200);
        //  }else{
        //     return response()->json([
        //         'message' => 'Logged out fail',
        //     ], 401);
        //  }
    }

    public function saveExpoToken(Request $request)
    {
        $user = Auth::user();
        $pushToken = $request->expo_push_token;

        if (!$pushToken) {
            return response()->json(['error' => 'No token provided'], 400);
        }

        $user->expo_push_token = $pushToken;
        $user->save();

        return response()->json(['message' => 'Expo push token saved successfully']);
    }
}
