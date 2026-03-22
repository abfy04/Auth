<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        if (! auth()->user()->email_verified_at) {
            auth()->logout();
            return response()->json(['message' => 'Email not verified. Please verify your email before logging in.'], 403);
        }

        return response()->json(['token' => $token]);
    }

    public function profile(Request $request)
    {
        $account = auth()->user();
        if (!$account) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $account->load('roles', 'user', 'prvider','admin');
        return response()->json($account);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function refresh(Request $request)
    {
        return response()->json(['token' => auth()->refresh()]);
    }
}
