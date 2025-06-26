<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $fillable = ['name'];

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }
}
