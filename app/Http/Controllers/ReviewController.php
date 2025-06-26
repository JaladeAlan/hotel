<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        // Validate input
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'review' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Ensure the user is the one who made the booking
        $booking = Booking::findOrFail($request->booking_id);
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['error' => 'You can only review your own bookings'], 403);
        }

        if ($booking->review) {
            return response()->json(['message' => 'You have already reviewed this booking.'], 400);
        }

        // Create the review
        $review = Review::create([
            'user_id' => Auth::id(),
            'booking_id' => $request->booking_id,
            'review' => $request->review,
            'rating' => $request->rating,
        ]);

          // Update the hotel's average rating
        $hotel = $room->hotel;
        $hotel->update([
            'rating' => $hotel->averageRating()
        ]);
        
        return response()->json($review, 201);
    }

     public function userReviews()
    {
        return response()->json(Auth::user()->reviews);
    }
}
