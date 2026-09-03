<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DonationController extends Controller
{
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

    public function index(Request $request)
    {
        $donations = Donation::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'donations' => $donations,
        ]);
    }

    public function show(Request $request, Donation $donation)
    {
        if ($donation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this donation.');
        }

        return response()->json([
            'donation' => $donation,
        ]);
    }

    public function update(Request $request, Donation $donation)
    {
        if ($donation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this donation.');
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'category' => 'sometimes|required|string|max:255',
            'condition' => 'sometimes|nullable|string|max:255',
            'location' => 'sometimes|nullable|string|max:255',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:5120',
            'quantity' => 'sometimes|required|integer|min:1',
            'preferred_date' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string',
        ]);

        $oldImage = null;

        if ($request->hasFile('image')) {
            $oldImage = $donation->image;
            $validated['image'] = $request->file('image')->store('donations', 'public');
        } else {
            unset($validated['image']);
        }

        $donation->update($validated);

        if ($oldImage && $oldImage !== $donation->image) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json([
            'message' => 'Donation updated successfully',
            'donation' => $donation->fresh(),
        ]);
    }

    public function destroy(Request $request, Donation $donation)
    {
        if ($donation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this donation.');
        }

        $donation->delete();

        return response()->json([
            'message' => 'Donation deleted successfully',
        ]);
    }

    public function myImpact(Request $request)
    {
        $donations = Donation::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $totalDonations = $donations->count();
        $totalItems = $donations->sum('quantity');
        $impactAreas = $donations->groupBy('category')
            ->map(fn ($items, $category) => [
                'category' => $category,
                'items' => $items->sum('quantity'),
            ])
            ->values();

        return response()->json([
            'impact' => [
                'total_donations' => $totalDonations,
                'total_items' => $totalItems,
                'impact_score' => min($totalItems * 10, 100),
                'impact_growth' => 0,
                'impact_areas' => $impactAreas,
                'recent_donations' => $donations->take(5),
            ],
        ]);
    }
}
