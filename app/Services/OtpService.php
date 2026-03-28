<?php
namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;

class OtpService
{
    protected int $otpExpiry = 600;   // 10 minutes
    protected int $rateLimitMax = 5;  // max requests
    protected int $rateLimitWindow = 3600; // 1 hour

    // Generate a 6-digit OTP
    public function generateOtp(): string
    {
        return rand(100000, 999999);
    }

    // Send OTP email
    public function sendOtp(string $email): void
    {
        $this->checkRateLimit($email);

        $otp = $this->generateOtp();
        // Store OTP in Redis
        Redis::setex("otp:{$email}", $this->otpExpiry, Hash::make($otp));
        // Queue OTP email
        SendEmailJob::dispatch(new OtpMail($otp),$email);
    }

    // Verify OTP
    public function verifyOtp(string $email, string|int $otp): bool
    {
        $this->checkRateLimit($email);

        $storedOtp = Redis::get("otp:{$email}");

        if (!$storedOtp) {
            throw ValidationException::withMessages(['otp' => 'OTP expired or not found']);
        }

        if (Hash::check($otp ,$storedOtp)) {
            throw ValidationException::withMessages(['otp' => 'Invalid OTP']);
        }

        // OTP is valid, delete it
        Redis::del("otp:{$email}");
        Redis::setex("otp:verified:{$email}", $this->otpExpiry, 'true'); // Mark as verified for password reset

        return true;
    }

    // Rate limit helper
    protected function checkRateLimit(string $email): void
    {
        $limitKey = "otp:rate:{$email}";
        $requests = Redis::get($limitKey) ?? 0;

        if ($requests >= $this->rateLimitMax) {
            throw ValidationException::withMessages(['otp' => 'Too many OTP requests. Try again later.']);
        }

        Redis::incr($limitKey);
        Redis::expire($limitKey, $this->rateLimitWindow);
    }

    public function isOtpVerified(string $email): bool
    {
        return Redis::get("otp:verified:{$email}") === 'true';
    }

    public function clearVerifiedOtp(string $email): void
    {
        Redis::del("otp:verified:{$email}");
    }
}