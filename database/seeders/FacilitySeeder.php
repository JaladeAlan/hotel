<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Facility;
use App\Models\Room;

class FacilitySeeder extends Seeder
{
public function run()
{
    $facilities = [
        'wifi',
        'tv',
        'air_conditioner',
        'parking',
        'pool'
    ];

    foreach ($facilities as $name) {
        Facility::create(['name' => $name]);
    }

    // Optional: attach random facilities to each room
    $allFacilities = Facility::all();

    foreach (Room::all() as $room) {
        $room->facilities()->sync(
            $allFacilities->random(rand(1, 3))->pluck('id')->toArray()
        );
    }
}
}