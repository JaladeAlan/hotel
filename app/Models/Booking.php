<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'total_price',
        'status',
        'check_in_time',
        'check_out_time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }


    public function review()
    {
        return $this->hasOne(Review::class);
    }


}
