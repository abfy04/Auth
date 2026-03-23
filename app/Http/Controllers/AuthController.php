<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = auth()->attempt($credentials)) {
            return ApiResponse::error('Invalid credentials',401);
        }
        if (! auth()->user()->email_verified_at) {
            auth()->logout();
            return ApiResponse::error('Email not verified. Please verify your email before logging in.',403);
        }

        return ApiResponse::success('Logged In successully',200,['token' => $token]);
    }

    public function profile(Request $request)
    {
        $account = auth()->user();
        if (!$account) {
            return ApiResponse::error('User not found',404) ;
        }
        $account->load('roles', 'user', 'provider');
        return ApiResponse::success($data=new AccountResource($account));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return ApiResponse::success('Logged out successfully');
    }

    public function refresh(Request $request)
    {
        return ApiResponse::success(['token'=>auth()->refresh()]);
    }
}
