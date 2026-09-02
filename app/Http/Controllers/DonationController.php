<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    // Create a donation
    public function store(Request $request)
    {
       $validated = $request->validate([
    'title' => 'required|string|max:255',
    'description' => 'nullable|string',
    'category' => 'required|string|max:255',
    'condition' => 'nullable|string|max:255',
    'location' => 'nullable|string|max:255',
    'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',

    'quantity' => 'required|integer|min:1',
    'preferred_date' => 'nullable|date',
    'notes' => 'nullable|string',
]);
if ($request->hasFile('image')) {
    $validated['image'] = $request->file('image')->store('donations', 'public');
}
        $donation = Donation::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Donation created successfully',
            'donation' => $donation,
        ], 201);
    }

    // Get all donations
    public function index()
    {
        $donations = Donation::with('user')->latest()->get();

        return response()->json([
            'donations' => $donations,
        ]);
    }

    // Get one donation
    public function show(Donation $donation)
    {
        return response()->json([
            'donation' => $donation->load('user'),
        ]);
    }

    // Update donation
    public function update(Request $request, Donation $donation)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|max:255',
            'condition' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
        ]);

        $donation->update($validated);

        return response()->json([
            'message' => 'Donation updated successfully',
            'donation' => $donation,
        ]);
    }

    // Delete donation
    public function destroy(Donation $donation)
    {
        $donation->delete();

        return response()->json([
            'message' => 'Donation deleted successfully',
        ]);
    }
}