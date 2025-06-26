<?php

namespace App\Http\Controllers;

use App\Models\AddOnService;
use App\Models\BookedService;
use Illuminate\Http\Request;

class AddOnServiceController extends Controller
{
    public function list()
    {
        return AddOnService::all();
    }

    public function book(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'add_on_service_id' => 'required|exists:add_on_services,id',
        ]);

        $bookedService = BookedService::create($request->all());
        return response()->json($bookedService, 201);
    }

    public function bookedServices($bookingId)
    {
        $booked = BookedService::where('booking_id', $bookingId)
            ->with('addOnService')
            ->get();
        return response()->json($booked);
    }
}
