<?php
namespace App\Http\Controllers;

use App\Models\Borrower;
use Illuminate\Http\Request;

class BorrowerController extends Controller
{
    public function index()
    {
        return Borrower::with('user')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        return Borrower::create($validated);

       
    }

    public function show($id)
    {
        return Borrower::with('user')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $borrower = Borrower::findOrFail($id);

        // Assuming only updatable field is user_id
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $borrower->update($validated);

        return $borrower;
    }

    public function destroy($id)
    {
        $borrower = Borrower::findOrFail($id);
        $borrower->delete();

        return response()->noContent();
    }
}