<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    // Search hotels based on location, date, or amenities
    public function search(Request $request)
    {
        $query = Hotel::query();

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by amenities (comma-separated values)
        if ($request->has('amenities')) {
            $amenities = explode(',', $request->amenities);
            $query->whereHas('amenities', function ($q) use ($amenities) {
                $q->whereIn('name', $amenities);
            });
        }

        // Filter by check-in and check-out dates (optional)
        if ($request->has('check_in_date') && $request->has('check_out_date')) {
            $query->whereHas('rooms', function ($q) use ($request) {
                $q->where('available_from', '<=', $request->check_in_date)
                  ->where('available_until', '>=', $request->check_out_date);
            });
        }

        // Fetch the hotels based on filters
        $hotels = $query->get();

        return response()->json($hotels);
    }
}
