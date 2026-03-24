<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use LoginService;
use LogoutService;
use RefreshTokenService;


class AuthController extends Controller
{
    // LOGIN
    public function login(LoginRequest $request, LoginService $loginService)
    {
        $credentials = $request->only('email', 'password');
        $userAgent = $request->header('User-Agent');
        $ip = $request->ip();

        $tokens = $loginService->login($credentials,$userAgent,$ip);

        return ApiResponse::success(
            'Logged in successfully',
            200,
            $tokens
        );
    }

    // LOGOUT
    public function logout(Request $request,LogoutService $logoutServie)
    {
        $userAgent = $request->header('User-Agent');
        $ip = $request->ip();
        $logoutServie->logout($userAgent,$ip);

        return ApiResponse::success('Logged out successfully');
    }

    // REFRESH ACCESS TOKEN
    public function refreshToken(Request $request,RefreshTokenService $refreshTokenService)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);
        $userAgent = $request->header('User-Agent');
        $ip = $request->ip();

        $rawToken = $request->input('refresh_token');

        $accessToken = $refreshTokenService->refreshToken($rawToken,$userAgent,$ip);


        return ApiResponse::success(
            'Access token refreshed',
            200,
            $accessToken
        );
    }

    // PROFILE
    public function profile(Request $request)
    {
        $account = auth()->user();
        if (! $account) return ApiResponse::error('User not found', 404);

        $account->load('roles', 'user', 'provider');

        return ApiResponse::success($account);
    }
}