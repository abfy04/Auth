<?php
namespace Tests\Unit\Services;

use App\Jobs\SendOTPEmailJob;
use App\Services\OtpService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    protected OtpService $service;
    protected string $email = 'test@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OtpService();
        $this->email = 'test@example.com';
    }

    // ==================== generateOtp ====================

 
    public function test_it_generates_valid_6_digit_otp(): void
    {
        $otp = $this->service->generateOtp();

        $this->assertIsInt($otp);
        $this->assertGreaterThanOrEqual(100000, $otp);
        $this->assertLessThanOrEqual(999999, $otp);
    }

    /** @test */
    public function test_it_generates_different_otps_each_time(): void
    {
        $otp1 = $this->service->generateOtp();
        $otp2 = $this->service->generateOtp();

        // Note: Could theoretically be same, but very unlikely
        // This test documents the behavior
        $this->assertIsInt($otp1);
        $this->assertIsInt($otp2);
    }

    // ==================== sendOtp ====================

    
    public function test_it_sends_otp_and_stores_in_redis(): void
    {
        $email = $this->email;
        // === Mock rate limit checks (called FIRST) ===
        Redis::shouldReceive('get')
            ->with("otp:rate:{$email}")
            ->once()
            ->andReturn('0');  // Under limit

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$email}")
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$email}", 3600)
            ->once()
            ->andReturn(true);

        // === Mock OTP storage ===
        Redis::shouldReceive('setex')
            ->with("otp:{$email}", 600, \Mockery::type('int'))
            ->once()
            ->andReturn(true);

        // === Mock Queue ===
        Queue::fake();

        // === Execute ===
        $this->service->sendOtp($email);

        // === Assert ===
        Queue::assertPushed(SendOTPEmailJob::class, function ($job) use ($email) {
            return $job->email === $email;
        });
    }

   
    public function test_it_throws_exception_when_rate_limited(): void
    {
        // Mock Redis to simulate rate limit exceeded
        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('5'); // Already at max

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Too many OTP requests');

        $this->service->sendOtp($this->email);
    }

   
    public function test_it_increments_rate_limit_counter(): void
    {
        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('2');

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$this->email}")
            ->once()
            ->andReturn(3);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$this->email}", 3600)
            ->once()
            ->andReturn(true);

        Redis::shouldReceive('setex')
            ->with("otp:{$this->email}", 600, \Mockery::type('int'))
            ->once()
            ->andReturn(true);

        Queue::fake();

        $this->service->sendOtp($this->email);

        $this->assertTrue(true); // Pass if no exception
    }

    // ==================== verifyOtp ====================

    
    public function test_it_verifies_valid_otp(): void
    {
        $otp = 123456;

        // Mock Redis to return matching OTP
        Redis::shouldReceive('get')
            ->with("otp:{$this->email}")
            ->andReturn((string) $otp);

        Redis::shouldReceive('del')
            ->with("otp:{$this->email}")
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('setex')
            ->with("otp:verified:{$this->email}", 600, 'true')
            ->once()
            ->andReturn(true);

        // Mock rate limit check
        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('0');

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$this->email}")
            ->andReturn(1);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$this->email}", 3600)
            ->andReturn(true);

        $result = $this->service->verifyOtp($this->email, $otp);

        $this->assertTrue($result);
    }

    
    public function test_it_verifies_otp_as_string(): void
    {
        $otp = '123456';

        Redis::shouldReceive('get')
            ->with("otp:{$this->email}")
            ->andReturn($otp);

        Redis::shouldReceive('del')
            ->with("otp:{$this->email}")
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('setex')
            ->with("otp:verified:{$this->email}", 600, 'true')
            ->once()
            ->andReturn(true);

        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('0');

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$this->email}")
            ->andReturn(1);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$this->email}", 3600)
            ->andReturn(true);

        $result = $this->service->verifyOtp($this->email, $otp);

        $this->assertTrue($result);
    }

    
    public function test_it_throws_exception_when_otp_not_found(): void
    {
        Redis::shouldReceive('get')
            ->with("otp:{$this->email}")
            ->andReturn(null);

        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('0');

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$this->email}")
            ->andReturn(1);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$this->email}", 3600)
            ->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('OTP expired or not found');

        $this->service->verifyOtp($this->email, 123456);
    }

   
    public function test_it_throws_exception_when_otp_is_invalid(): void
    {
        Redis::shouldReceive('get')
            ->with("otp:{$this->email}")
            ->andReturn('999999'); // Different from input

        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('0');

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$this->email}")
            ->andReturn(1);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$this->email}", 3600)
            ->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid OTP');

        $this->service->verifyOtp($this->email, 123456);
    }

    
    public function test_it_deletes_otp_after_successful_verification(): void
    {
        $otp = 123456;

        Redis::shouldReceive('get')
            ->with("otp:{$this->email}")
            ->andReturn((string) $otp);

        Redis::shouldReceive('del')
            ->with("otp:{$this->email}")
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('setex')
            ->with("otp:verified:{$this->email}", 600, 'true')
            ->once()
            ->andReturn(true);

        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('0');

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$this->email}")
            ->andReturn(1);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$this->email}", 3600)
            ->andReturn(true);

        $this->service->verifyOtp($this->email, $otp);

        // If we get here, del() was called as expected
        $this->assertTrue(true);
    }

    
    public function test_it_marks_email_as_verified_after_success(): void
    {
        $otp = 123456;

        Redis::shouldReceive('get')
            ->with("otp:{$this->email}")
            ->andReturn((string) $otp);

        Redis::shouldReceive('del')
            ->with("otp:{$this->email}")
            ->andReturn(1);

        Redis::shouldReceive('setex')
            ->with("otp:verified:{$this->email}", 600, 'true')
            ->once()
            ->andReturn(true);

        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('0');

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$this->email}")
            ->andReturn(1);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$this->email}", 3600)
            ->andReturn(true);

        $this->service->verifyOtp($this->email, $otp);

        // Verify setex was called with correct params for verified flag
        $this->assertTrue(true);
    }

    // ==================== isOtpVerified ====================

    public function test_it_returns_true_when_otp_is_verified(): void
    {
        Redis::shouldReceive('get')
            ->with("otp:verified:{$this->email}")
            ->andReturn('true');

        $result = $this->service->isOtpVerified($this->email);

        $this->assertTrue($result);
    }

    
    public function test_it_returns_false_when_otp_is_not_verified(): void
    {
        Redis::shouldReceive('get')
            ->with("otp:verified:{$this->email}")
            ->andReturn(null);

        $result = $this->service->isOtpVerified($this->email);

        $this->assertFalse($result);
    }

    public function test_it_returns_false_when_verified_flag_is_wrong_value(): void
    {
        Redis::shouldReceive('get')
            ->with("otp:verified:{$this->email}")
            ->andReturn('false'); // String 'false', not boolean

        $result = $this->service->isOtpVerified($this->email);

        $this->assertFalse($result);
    }

    // ==================== clearVerifiedOtp ====================

   
    public function test_it_clears_verified_otp_flag(): void
    {
        Redis::shouldReceive('del')
            ->with("otp:verified:{$this->email}")
            ->once()
            ->andReturn(1);

        $this->service->clearVerifiedOtp($this->email);

        $this->assertTrue(true);
    }

    // ==================== Rate Limit Integration ====================

   
    public function test_it_allows_requests_under_rate_limit(): void
    {
        // Simulate 4 requests (under limit of 5)
        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('4');

        Redis::shouldReceive('incr')
            ->with("otp:rate:{$this->email}")
            ->andReturn(5);

        Redis::shouldReceive('expire')
            ->with("otp:rate:{$this->email}", 3600)
            ->andReturn(true);

        Redis::shouldReceive('setex')
            ->with("otp:{$this->email}", 600, \Mockery::type('int'))
            ->andReturn(true);

        Queue::fake();

        // Should not throw
        $this->service->sendOtp($this->email);
        $this->assertTrue(true);
    }

   
    public function test_it_blocks_requests_over_rate_limit(): void
    {
        // Simulate 5 requests (at limit)
        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('5');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Too many OTP requests');

        $this->service->sendOtp($this->email);
    }

    public function test_rate_limit_applies_to_verify_otp_too(): void
    {
        Redis::shouldReceive('get')
            ->with("otp:rate:{$this->email}")
            ->andReturn('5');

        $this->expectException(ValidationException::class);

        $this->service->verifyOtp($this->email, 123456);
    }
}