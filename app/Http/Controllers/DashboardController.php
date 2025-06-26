<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function overview()
    {
        // Active Bookings: Check-ins today or already checked in
        $activeBookings = Booking::where('check_in_date', '<=', now())
            ->where('check_out_date', '>=', now())
            ->with('room', 'user')
            ->get();

        // Upcoming Bookings: Check-ins in the future
        $upcomingBookings = Booking::where('check_in_date', '>', now())
            ->with('room', 'user')
            ->get();

        return response()->json([
            'active_bookings' => $activeBookings,
            'upcoming_bookings' => $upcomingBookings,
        ]);
    }

    public function analytics()
    {
        // Calculate occupancy rate
        $totalRooms = \App\Models\Room::count();
        $occupiedRooms = Booking::where('check_in_date', '<=', now())
            ->where('check_out_date', '>=', now())
            ->distinct('room_id')
            ->count('room_id');
        $occupancyRate = $totalRooms > 0 ? ($occupiedRooms / $totalRooms) * 100 : 0;

        // Calculate total revenue
        $totalRevenue = Booking::whereYear('check_in_date', now()->year)
            ->sum('total_amount');

        // Customer Preferences: Popular room types
        $popularRoomTypes = \App\Models\Room::join('bookings', 'rooms.id', '=', 'bookings.room_id')
            ->select('rooms.room_type', \DB::raw('count(*) as count'))
            ->groupBy('rooms.room_type')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'occupancy_rate' => $occupancyRate,
            'total_revenue' => $totalRevenue,
            'popular_room_types' => $popularRoomTypes,
        ]);
    }

}
