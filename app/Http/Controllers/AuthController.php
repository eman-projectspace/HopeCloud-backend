<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the signup data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // Create an authentication token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Send response back to React
        return response()->json([
            'message' => 'Account created successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}