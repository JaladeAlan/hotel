<?php

namespace App\Http\Controllers;

use App\Models\RoomServiceRequest;
use Illuminate\Http\Request;

class RoomServiceController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'service_type' => 'required|string',
            'description' => 'nullable|string',
            'cost' => 'required|numeric',
        ]);

        $service = RoomServiceRequest::create($request->all());
        return response()->json($service, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string|in:pending,in-progress,completed']);

        $service = RoomServiceRequest::findOrFail($id);
        $service->update(['status' => $request->status]);

        return response()->json($service);
    }

    public function listByBooking($bookingId)
    {
        $services = RoomServiceRequest::where('booking_id', $bookingId)->get();
        return response()->json($services);
    }
}
    