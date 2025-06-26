<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    public function index()
    {
        return Staff::with('tasks')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'role' => 'required|string',
            'phone' => 'required|string',
        ]);

        $staff = Staff::create($request->all());
        return response()->json($staff, 201);
    }
}
