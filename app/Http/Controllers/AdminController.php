<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function updateDonationStatus(
        Request $request,
        Donation $donation
    ): JsonResponse {
        // Only administrators can approve or reject donations
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized admin access.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $donation->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Donation status updated successfully.',
            'donation' => [
                'id' => $donation->id,
                'status' => $donation->status,
            ],
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        // Only administrators can access the admin dashboard
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized admin access.'
            ], 403);
        }

        $totalDonations = Donation::count();

        $pendingDonations = Donation::where(
            'status',
            'submitted'
        )->count();

        $approvedDonations = Donation::where(
            'status',
            'approved'
        )->count();

        $totalUsers = User::where(
            'role',
            'user'
        )->count();

        $recentDonations = Donation::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($donation) {
                return [
                    'id' => $donation->id,
                    'donor' => $donation->user?->name
                        ?? 'Unknown donor',
                    'item' => $donation->title
                        ?? 'Untitled donation',
                    'category' => $donation->category
                        ?? 'Other',
                    'status' => $donation->status
                        ?? 'submitted',
                    'date' => $donation->created_at?->format(
                        'M d, Y'
                    ),
                ];
            });

        $approvalRate = $totalDonations > 0
            ? round(
                ($approvedDonations / $totalDonations) * 100
            )
            : 0;

        return response()->json([
            'stats' => [
                'total_donations' => $totalDonations,
                'pending_donations' => $pendingDonations,
                'approved_donations' => $approvedDonations,
                'total_users' => $totalUsers,
                'approval_rate' => $approvalRate,
            ],
            'recent_donations' => $recentDonations,
        ]);
    }
}