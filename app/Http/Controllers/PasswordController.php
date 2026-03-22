<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\Hash;


use App\Http\Requests\VerifyEmailRequest;
use App\Http\Requests\ChangePasswordRequest;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\StoreUserRequest;

use App\Services\UserService;
use App\Services\ProviderService;
use App\Services\AccountService;
use App\Services\OtpService;

use App\Models\Account;

use Illuminate\Http\Request;

class PasswordController extends Controller
{
    
    public function resetPassword(ResetPasswordRequest $request ,OtpService $otpService, AccountService $accountService)
    {
        $validatedData= $request->validated();

        $email = $validatedData['email'];

        $account = $accountService->findByEmail($email);

        if (!$account) {
            return response()->json(['message' => 'Email not found'], 404);
        }
        if (! $otpService->isOtpVerified($email)) {
            return response()->json(['message' => 'OTP not verified. Please verify OTP before resetting password.'], 400);
        }

        $newPassword = $request->input('new_password');
        $accountService->changePassword($account, $newPassword);
        $otpService->clearVerifiedOtp($email); // Clear OTP verification status after password reset

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function changePassword(ChangePasswordRequest $request,AccountService $accountService)
    {
        $validatedData = $request->validated();

        $account= auth()->user();
        if (!$account) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        if (!Hash::check($validatedData['current_password'], $account->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        $newPassword = $validatedData['new_password'];
        $accountService->changePassword($account, $newPassword);

        return response()->json(['message' => 'Password changed successfully']);
    }
}