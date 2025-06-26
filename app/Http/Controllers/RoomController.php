<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Facility;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::where('is_available', true)
        ->with('facilities:id,name')
        ->get()
        ->map(function ($room) {
            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->room_type,
                'capacity' => $room->capacity,
                'price_per_night' => $room->price_per_night,
                'is_available' => $room->is_available,
                'rating' => $room->rating,
                'created_at' => $room->created_at,
                'updated_at' => $room->updated_at,
                'facilities' => $room->facilities->pluck('name'),
            ];
        });
    }

    public function storeRoom(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'room_number' => 'required|unique:rooms',
                'room_type' => 'required',
                'capacity' => 'required|integer',
                'price_per_night' => 'required|numeric',
                'facility_ids' => 'sometimes|array',
                'facility_ids.*' => 'exists:facilities,id',
            ]);

            // Create room
            $room = Room::create($validated);

            if (!empty($validated['facility_ids'])) {
            $room->facilities()->sync($validated['facility_ids']);
            }

            return response()->json([
                'message' => 'Room created successfully',
                'room' => $room
            ], 201);

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

     // Check Room Availability
    public function checkAvailability(Request $request)
    {
        try {
            $request->validate([
                'check_in_date' => 'required|date|after:today',
                'check_out_date' => 'required|date|after:check_in_date',
            ]);

            $rooms = Room::where('is_available', true)
                ->whereDoesntHave('bookings', function ($query) use ($request) {
                    $query->where('check_in_date', '<', $request->check_out_date)
                        ->where('check_out_date', '>', $request->check_in_date);
                })
                ->get();

            return response()->json([
                'available_rooms' => $rooms,
                'message' => 'Available rooms fetched successfully'
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

        // Filter rooms based on price, rating, room type, and facilities
    public function filter(Request $request)
    {
        try {
            $query = Room::query();

            if ($request->filled('min_price') && $request->filled('max_price')) {
                $query->whereBetween('price_per_night', [
                    $request->min_price, $request->max_price
                ]);
            }

            if ($request->filled('rating')) {
                $query->where('rating', '>=', $request->rating);
            }

            if ($request->filled('room_type')) {
                $query->where('room_type', $request->room_type);
            }

            if ($request->filled('facilities')) {
                $query->whereHas('facilities', function ($q) use ($request) {
                    $q->whereIn('name', $request->facilities); // Expecting array
                });
            }

            if ($request->boolean('available_only')) {
                $query->where('is_available', true);
            }

            $rooms = $query->with('facilities')->get();

            return response()->json([
                'message' => 'Filtered rooms retrieved successfully',
                'rooms' => $rooms
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

  public function show($roomId)
    {
        try {
            $room = Room::with(['reviews', 'facilities'])->findOrFail($roomId);

            $facilityNames = $room->facilities->pluck('name');

            return response()->json([
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->room_type,
                'capacity' => $room->capacity,
                'price_per_night' => $room->price_per_night,
                'is_available' => $room->is_available,
                'rating' => $room->rating,
                'created_at' => $room->created_at,
                'updated_at' => $room->updated_at,
                'average_rating' => $room->averageRating(),
                'facilities' => $facilityNames
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Room not available'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function showUserReviews()
    {
        $reviews = Auth::user()->reviews;  
        return response()->json($reviews);
    }

  public function update(Request $request, $id)
    {
        try {
            $room = Room::findOrFail($id);
    
            $validated = $request->validate([
                'room_number' => 'sometimes|required|unique:rooms,room_number,' . $room->id,
                'room_type' => 'sometimes|required',
                'capacity' => 'sometimes|required|integer',
                'price_per_night' => 'sometimes|required|numeric',
                'is_available' => 'sometimes|boolean',
                'facility_ids' => 'sometimes|array',
                'facility_ids.*' => 'exists:facilities,id',
            ]);

            $room->update($validated);

            if (isset($validated['facility_ids'])) {
                $room->facilities()->sync($validated['facility_ids']);
            }

            return response()->json([
                'message' => 'Room updated successfully',
                'room' => $room->load('facilities'),
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

    public function destroy($id)
    {
        try {
            $room = Room::findOrFail($id);
            $room->delete();

            return response()->json([
                'message' => 'Room deleted successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Room not found',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function facilities(Request $request)
    {
        try {
            $facilities = Facility::all(['id', 'name']); 

            return response()->json([
                'message' => 'Facilities retrieved successfully',
                'facilities' => $facilities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch facilities',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}

