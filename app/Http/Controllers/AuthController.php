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
use App\Events\UserOnline;
use App\Events\UserOffline;

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
        $user->terms_and_conditions = $request->terms_and_conditions;
    
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


    public function upload_profile_picture(Request $request): JsonResponse
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'profile_picture' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);
    
            // Handle file upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                $filePath = $file->store('profile-photo', 'public'); // Store in 'storage/app/public/collaterals'
    
                // Find user and update profile picture path
                $user = User::findOrFail($request->user_id);
                $user->profile_photo_path = $filePath;
                $user->save();
    
                return response()->json([
                    'message' => 'Profile picture uploaded successfully',
                    'user' => $user,
                ], 201);
            }
    
            return response()->json(['error' => 'File upload failed'], 400);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Catch validation errors and return a JSON response
            return response()->json([
                'errors' => $e->errors(),  // Returns validation error details
                'message' => 'Validation failed',
            ], 422);  // HTTP 422 Unprocessable Entity for validation errors
        }
    }
    


    public function updatePersonalInfo(Request $request)
    {
        // Validate the incoming data
       $validatedData = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
          
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::user()->id,

            'password' => 'nullable|string|min:8', 
        'national_id' => 'nullable|string|max:50', 
            'traditional_authority' => 'nullable|string|max:255',
            'home_village' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'airtel_money_number' => 'nullable|string|max:20', 
            'mpamba_number' => 'nullable|string|max:20', 
            'home_physical_address' => 'nullable|string|max:255',
            'current_physical_address' => 'nullable|string|max:255',
            'physical_address' => 'nullable|string|max:255',
            'guardian' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|string|max:20', 
            'company_name' => 'nullable|string|max:255',
            'lending_limit' => 'nullable|numeric|min:0', 
            'lending_minimum' => 'nullable|numeric|min:0', 
            'interest_rate' => 'nullable|numeric|min:0|max:100', 
            'collateral_required' => 'nullable|boolean',
            'preferred_borrower_criteria' => 'nullable|string|max:255',
            'company_annual_revenue' => 'nullable|string|max:255',
            'business_registration_number' => 'nullable|string|max:255',
            'profile_photo_path' => 'nullable|string|max:255',
            'terms_and_conditions' => 'nullable|string', 
        
        ]);


        // Get the authenticated user
            $user = Auth::user();

            // Check if a password is provided and hash it if so
            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            } else {
                // Remove the password key if it's not provided to avoid overwriting
                unset($validatedData['password']);
            }

            // Update the user's personal information
            $user->update($validatedData);

            // Return the updated user information
            return response()->json([
                'message' => 'Personal information updated successfully',
                'user' => $user,
            ]);
    }

    public function getPersonalInfo(): JsonResponse
    {
       
        $user = Auth::user();
        

    
        return response()->json([
         'user' => new UserResource($user),        
        ], 200);
       
    }

    public function chatActivity(Request $request)
    {
        $user = auth()->user(); 
        $user->updateLastSeen(); 

        return response()->json(['status' => 'success']);
    }

    public function status(Request $request,$id)
    {
        $user = User::find($id);

        return response()->json([
            'is_online' => $user->is_online,
            'last_seen' => $user->last_seen,
        ]);
    }

    public function updateLastSeen(Request $request)
    {
        $user = auth()->user();
        $user->last_seen = now();
        $user->is_online = false; 
        $user->save();

        return response()->json([
            'is_online' => $user->is_online,
            'last_seen' => $user->last_seen,
        ]);

    }



        public function updateUserStatus(Request $request)
        {
            $user = Auth::user();
            
            if ($request->is_online) {
                // User is online
                broadcast(new UserOnline($user->id));
                // Update user's status in the database as needed
            } else {
                // User is offline
                $last_seen = now(); // Set last seen time
                broadcast(new UserOffline($user->id, $last_seen));
                // Update user's status in the database as needed
            }

            return response()->json(['status' => 'success']);
        }




    

}
