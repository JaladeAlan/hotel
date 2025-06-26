<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    // Show user profile and preferences
    public function show()
    {
        $user = Auth::user();
        return response()->json($user);
    }

    // Update user profile and preferences
    public function update(Request $request)
    {
        $request->validate([
            'preferred_room_type' => 'nullable|string',
            'meal_plan' => 'nullable|string',
            'loyalty_points' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $user->preferred_room_type = $request->preferred_room_type ?? $user->preferred_room_type;
        $user->meal_plan = $request->meal_plan ?? $user->meal_plan;
        $user->loyalty_points = $request->loyalty_points ?? $user->loyalty_points;

        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }

    // Redeem loyalty points
    public function redeemLoyaltyPoints(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        if ($user->redeemLoyaltyPoints($request->points)) {
            return response()->json(['message' => 'Loyalty points redeemed successfully']);
        }

        return response()->json(['message' => 'Insufficient loyalty points'], 400);
    }

    // Add loyalty points
    public function addLoyaltyPoints(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $user->addLoyaltyPoints($request->points);

        return response()->json(['message' => 'Loyalty points added successfully']);
    }
}
