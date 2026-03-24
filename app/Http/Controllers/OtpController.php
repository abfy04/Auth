<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\VerifyEmailRequest;

use App\Services\AccountService;
use App\Services\OtpService;
use App\Http\Responses\ApiResponse;

class OtpController extends Controller
{

    public function verifyEmail(VerifyEmailRequest $request,OtpService $otpService, AccountService $accountService)
    {
            $validatedData = $request->validated();

            $otp = $validatedData['otp'];
            $email = $validatedData['email'];

            // Check if OTP is valid
            if (!$otpService->verifyOtp($email, $otp)) {
                return ApiResponse::error('Invalid OTP',400);
            }
            $accountService->verifyEmail($email);
            return ApiResponse::success('Email verified successfully');
           
    }
     public function verifyPasswordResetOpt(VerifyEmailRequest $request, OtpService $otpService)
    {
        $validatedData = $request->validated();

        $otp = $validatedData['otp'];
        $email = $validatedData['email'];

        // Check if OTP is valid
        if (!$otpService->verifyOtp($email, $otp)) {
            return ApiResponse::error('Invalid OTP',400);
        }

        return ApiResponse::success('OTP verified. You can now reset your password.');

    }


    public function requestPasswordReset(Request $request, OtpService $otpService, AccountService $accountService)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');
        $accountService->requestChangeReset($email);
        $otpService->sendOtp($email);

        return ApiResponse::success('Password reset OTP sent successfully');
        
    }
    public function requestChangeEmail(Request $request,OtpService $otpService,AccountService $accountService)
    {
        $request->validate(['new_email' => 'required|email']);
        $account = auth()->user();
        if (!$account) {
            return ApiResponse::error('User not found',404);
        }

        $newEmail = $request->input('new_email');
        $accountService->changeEmail($account,$newEmail);

        $otpService->sendOtp($newEmail); // Send OTP to new email for verification
        auth()->logout();
        return ApiResponse::success('Email change requested. Please verify your new email.');
      
    }

   
}
