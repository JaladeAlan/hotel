<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'room_type',
        'capacity',
        'price_per_night',
        'is_available',
        'rating',
        'last_occupied_at',
        'status'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasManyThrough(Review::class, Booking::class);
    }

    public function averageRating()
    {
        return $this->reviews()->avg('rating');
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class);
    }


}
