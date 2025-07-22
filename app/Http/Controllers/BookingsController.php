<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;
use Carbon\Carbon;

class BookingsController extends Controller
{
    // Make a new booking
    public function booking(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        $checkIn = Carbon::parse($request->check_in_date)->startOfDay();
        $checkOut = Carbon::parse($request->check_out_date)->startOfDay();

        $days = $checkIn->diffInDays($checkOut);
        if ($days < 1) {
            return response()->json(['error' => 'Minimum stay is one night'], 400);
        }

        $room = Room::findOrFail($request->room_id);

        if (!$room->is_available) {
            return response()->json(['error' => 'Room is currently not available'], 400);
        }

        // Check for overlapping bookings
        $overlapping = Booking::where('room_id', $room->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>=', $checkOut);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json(['error' => 'Room already booked for selected dates'], 400);
        }

        $totalPrice = $days * $room->price_per_night;

        if ($this->isPeakSeason($checkIn)) {
            $totalPrice *= 1.2; // 20% increase
        }

        DB::beginTransaction();
        try {
            $booking = Booking::create([
                'user_id' => auth()->id(),
                'room_id' => $room->id,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_price' => $totalPrice,
                'status' => 'confirmed',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Booking successful',
                'booking' => $booking,
                'total_price' => round($totalPrice, 2)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Booking failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Cancel an existing booking
    public function cancelBooking($id)
    {
        $booking = Booking::findOrFail($id);

        // Secure cancellation: only owner can cancel
        if ($booking->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Booking already cancelled'], 400);
        }

        if ($booking->check_in_date > now()) {
            $booking->update(['status' => 'cancelled']);
            return response()->json(['message' => 'Booking cancelled successfully']);
        }

        return response()->json(['message' => 'Cannot cancel a booking after check-in'], 400);
    }

    // Check if a date is in peak season
    private function isPeakSeason($checkInDate)
    {
        return $checkInDate->month == 12;
    }

    public function myBookings()
    {
        return response()->json([
            'bookings' => Booking::with('room')
                ->where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->get()
        ]);
    }

   public function filterBookings(Request $request)
    {
        $request->validate([
            'room_id' => 'sometimes|exists:rooms,id',
            'status' => 'sometimes|in:confirmed,cancelled,pending',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
        ]);

        $query = Booking::with('room')
            ->where('user_id', auth()->id());

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('check_in_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('check_out_date', '<=', $request->to_date);
        }

        $bookings = $query->orderByDesc('check_in_date')->get();

        return response()->json([
            'message' => 'Filtered bookings retrieved successfully',
            'bookings' => $bookings
        ]);
    }

    public function listAllBookings()
    {
        try {
            $bookings = Booking::with(['room:id,room_number,room_type', 'user:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'All bookings retrieved successfully',
                'bookings' => $bookings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve bookings',
                'message' => $e->getMessage()
            ], 500);
        }
    }

  public function checkIn($bookingId)
    {
        try {
            $booking = Booking::with('room')->findOrFail($bookingId);

            if ($booking->status !== 'confirmed') {
                return response()->json(['error' => 'Booking is not eligible for check-in'], 400);
            }

            if (Carbon::now()->lt(Carbon::parse($booking->check_in_date))) {
                return response()->json(['error' => 'Cannot check in before the scheduled date'], 400);
            }

            // Update booking and room
            $booking->update([
                'status' => 'checked_in',
                'check_in_time' => now(),
            ]);

            $booking->room->update([
                'is_available' => false,
                'status' => 'occupied',
                'last_occupied_at' => now(),
            ]);

            return response()->json(['message' => 'Check-in successful']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Booking not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function extendStay(Request $request, $bookingId)
    {
        try {
            $request->validate([
                'new_check_out_date' => 'required|date|after:today',
            ]);

            $booking = Booking::findOrFail($bookingId);

            if (strtotime($request->new_check_out_date) <= strtotime($booking->check_out_date)) {
                return response()->json([
                    'error' => 'New check-out date must be after the current check-out date.'
                ], 422);
            }
            
            // Check if room is available for extension period
            $conflict = Booking::where('room_id', $booking->room_id)
                ->where('id', '!=', $booking->id)
                ->where('check_in_date', '<', $request->new_check_out_date)
                ->where('check_out_date', '>', $booking->check_out_date)
                ->exists();

            if ($conflict) {
                return response()->json([
                    'error' => 'Room is not available for the extended period.'
                ], 409);
            }

            // Update booking
            $booking->check_out_date = $request->new_check_out_date;
            $booking->save();

            return response()->json([
                'message' => 'Stay extended successfully',
                'booking' => $booking
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'messages' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function checkOut($bookingId)
    {
        try {
        $booking = Booking::with('room')->findOrFail($bookingId);

        if ($booking->status !== 'checked_in') {
            return response()->json(['error' => 'Booking is not eligible for check-out'], 400);
        }

        if (Carbon::now()->lt(Carbon::parse($booking->check_out_date))) {
            return response()->json(['error' => 'Cannot check out before the scheduled date'], 400);
        }

        // Update booking and room
        $booking->update([
            'status' => 'checked_out',
            'check_out_time' => now(),
        ]);

        $booking->room->update([
            'is_available' => true,
            'status' => 'available',
        ]);

        return response()->json(['message' => 'Check-out successful']);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Booking not found',
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'An unexpected error occurred',
            'message' => $e->getMessage(),
        ], 500);
    }
}

    // Staff Check-In using Room Number
  public function staffCreateBookingAndCheckIn(Request $request)
    {
        try {
            $request->validate([
                'room_number' => 'required|string',
                'check_out_date' => 'required|date|after:today',
            ]);

            $room = Room::where('room_number', $request->room_number)->firstOrFail();

            if (!$room->is_available || $room->status !== 'available') {
                return response()->json(['error' => 'Room is not available for check-in'], 400);
            }
            $checkIn = Carbon::now()->timezone('Africa/Lagos')->startOfDay();
            $checkOut = Carbon::parse($request->check_out_date)->timezone('Africa/Lagos')->startOfDay();

            $numberOfNights = $checkIn->diffInDays($checkOut, false);

            if ($numberOfNights < 1) {
                return response()->json(['error' => 'Check-out must be at least one night after check-in'], 400);
            }

            $totalPrice = $room->price_per_night * $numberOfNights;

            DB::beginTransaction();

            // Dynamically create a temporary user
            $tempUser = User::create([
                'name' => 'Temporary Guest',
                'username' => 'temp' . rand(100000, 999999), 
                'email' => 'temp' . uniqid() . '@guest.com',
                'password' => bcrypt(Str::random(10)), 
            ]);

            // Create booking
            $booking = Booking::create([
                'user_id' => $tempUser->id,
                'room_id' => $room->id,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_price' => $totalPrice,
                'status' => 'checked_in',
                'check_in_time' => now(),
            ]);

            $room->update([
                'is_available' => false,
                'status' => 'occupied',
                'last_occupied_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Booking created and guest checked in successfully by staff',
                'booking' => $booking,
                'nights' => $numberOfNights,
                'guest' => $tempUser->only(['id', 'name', 'username']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    //Staff Check-Out using Room Number
    public function staffCheckOutByRoom(Request $request)
    {
        try {
            $request->validate(['room_number' => 'required|string']);

            $room = Room::where('room_number', $request->room_number)->firstOrFail();

            $booking = Booking::where('room_id', $room->id)
                ->where('status', 'checked_in')
                ->whereDate('check_out_date', now()->toDateString())
                ->first();

            if (!$booking) {
                return response()->json(['error' => 'No checked-in booking found for today in this room'], 404);
            }

            $booking->update([
                'status' => 'checked_out',
                'check_out_time' => now(),
            ]);

            $room->update([
                'is_available' => true,
                'status' => 'available',
            ]);

            return response()->json(['message' => 'Guest check-out successful (staff)']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }
}