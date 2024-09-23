<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\LenderResource;
use Illuminate\Http\JsonResponse;

class LenderController extends Controller
{
    public function index(): JsonResponse
    {
        $lenders = User::whereHas('accountType', function ($query) {
            $query->where('type', 'lender');
        })->with('banks')->get();

        return response()->json(LenderResource::collection($lenders), 200);
    }
}
