<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\StoreUserRequest;

use App\Http\Responses\ApiResponse;
use App\Services\OtpService;
use App\Services\UserService;
use App\Services\ProviderService;


class RegisterController extends Controller
{
    public function userRegister(
        StoreUserRequest $request,
         UserService $userService, 
         OtpService $otpService
    )
    {
        $validatedData = $request->validated(); 
        $user = $userService->createUser($validatedData);
        $otpService->sendOtp($user->account->email);

        return ApiResponse::success(
            $user,
            'Registration successful. Please verify your email.',
            201
        );
    }

     public function providerRegister(
        StoreProviderRequest $request, 
        ProviderService $providerService, 
        OtpService $otpService
    )
    {
        $validatedData = $request->validated();
       
        $provider = $providerService->createProvider($validatedData);
        $otpService->sendOtp($provider->account->email);
          

        return ApiResponse::success(
            $provider,
            'Registration successful. Please verify your email.',
            201
        );
    }
    
}
