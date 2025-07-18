<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

     User::create([
    'name' => 'Test User',
    'username' => 'testusers', 
    'email' => 'tests@example.com',
    'password' => Hash::make('password'), 
    'uid' => 'USR-' . Str::upper(Str::random(6)),
    ]);
        
        // Call FacilitySeeder
        $this->call(FacilitySeeder::class);
    }
}
