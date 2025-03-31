<?php
namespace App\Http\Controllers;

use App\Models\Collateral;
use App\Http\Requests\CollateralRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class CollateralController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        // Validate the request data
        // $validated = $request->validate([
        //     'collateral_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', 
          
        //     'name' => 'nullable|string|max:255',
        //     'description' => 'nullable|string',
        //     'value' => 'nullable|numeric',
        // ]);

        // Handle file upload
        if ($request->hasFile('collateral_file')) {
            $file = $request->file('collateral_file');
            $filePath = $file->store('collaterals', 'public'); // Store in 'storage/app/public/collaterals'

            // Save file details and collateral info in the database
            // $collateral = Collateral::create([
            //     'user_id' => Auth::user()->id,
            //     'name' => $validated['name'],
            //     'description' => $validated['description'],
            //     'value' => $validated['value'],
            //     'collateral_file' => $filePath, 
            //     'status' => 'available',
            // ]);

            $collateral = Collateral::create([
                'user_id' => Auth::user()->id,
                'name' => $request->name,
                'description' => $request->description,
                'value' => $request->value,
                'collateral_file' => $filePath, 
                'status' => 'available',
            ]);

            return response()->json([
                'message' => 'Collateral uploaded successfully',
                'collateral' => $collateral,
            ], 201);
        }

        return response()->json(['error' => 'File upload failed'], 400);
    }

    public function index(): JsonResponse
    {
        $collaterals = Collateral::where('user_id', Auth::id())->get();

        return response()->json($collaterals, 200);
    }

    public function available(): JsonResponse
    {
        $collaterals = Collateral::where(['user_id' => Auth::id(), 'status' => 'available'])->get();

        return response()->json($collaterals, 200);
    }

  
}
