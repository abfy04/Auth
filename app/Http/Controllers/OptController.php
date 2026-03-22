<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\VerifyEmailRequest;

use App\Services\AccountService;
use App\Services\OtpService;


class OptController extends Controller
{
    public function sendOtp($email)
    {
        $otpService = new OtpService();
        if($otpService->sendOtp($email)){
            return response()->json(['message' => 'OTP sent successfully'], 200);
        }
    }

    public function verifyEmail(VerifyEmailRequest $request,OtpService $otpService, AccountService $accountService)
        {
            $validatedData = $request->validated();

            $otp = $validatedData['otp'];
            $email = $validatedData['email'];

            // Check if OTP is valid
            if (!$otpService->verifyOtp($email, $otp)) {
                return response()->json(['message' => 'Invalid OTP'], 400);
            }
            $accountService->verifyEmail($email);
            return response()->json(['message' => 'Email verified successfully']);
    }

    public function requestPasswordReset(Request $request, OtpService $otpService, AccountService $accountService)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');
        $account = $accountService->findByEmail($email);
        if (!$account) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        if($otpService->sendOtp($email)){
            return response()->json(['message' => 'Password reset OTP sent successfully']);
        }
    }

    public function verifyPasswordResetOpt(VerifyEmailRequest $request, OtpService $otpService)
    {
        $validatedData = $request->validated();

        $otp = $validatedData['otp'];
        $email = $validatedData['email'];

        // Check if OTP is valid
        if (!$otpService->verifyOtp($email, $otp)) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        return response()->json(['message' => 'OTP verified. You can now reset your password.']);
    }


    public function requestChangeEmail(Request $request)
    {
        $request->validate(['new_email' => 'required|email']);
        $account = auth()->user();
        if (!$account) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $newEmail = $request->input('new_email');
        $account->email = $newEmail;
        $account->email_verified_at = null; // Mark email as unverified
        $account->save();

        $this->sendOtp($newEmail); // Send OTP to new email for verification

        return response()->json(['message' => 'Email change requested. Please verify your new email.']);
    }

    public function verifyChangeEmail(VerifyEmailRequest $request, OtpService $otpService,AccountService $accountService)
    {
        $validatedData = $request->validated();

        $otp = $validatedData['otp'];
        $email = $validatedData['email'];

        // Check if OTP is valid
        if (!$otpService->verifyOtp($email, $otp)) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        $accountService->verifyEmail($email);
        auth()->logout();
        return response()->json(['message' => 'Email change verified successfully']);
    }
}
