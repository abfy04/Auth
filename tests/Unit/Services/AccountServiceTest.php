<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AccountService;
use App\Exceptions\ServiceException;
use App\Models\Account;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;
   

    protected AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountService();
    }

    // ==================== findByEmail ====================

    /** @test */
    public function test_it_finds_account_by_email(): void
    {
        $account = Account::factory()->create(['email' => 'test@example.com']);

        $result = $this->service->findByEmail('test@example.com');

        $this->assertInstanceOf(Account::class, $result);
        $this->assertEquals($account->id, $result->id);
    }

    /** @test */
    public function test_it_throws_exception_when_account_not_found_by_email(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Account not found');
        $this->expectExceptionCode(404);

        $this->service->findByEmail('nonexistent@example.com');
       
    }

    // ==================== findByID ====================

    /** @test */
    public function test_it_finds_account_by_id(): void
    {
        $account = Account::factory()->create();

        $result = $this->service->findByID($account->id);

        $this->assertInstanceOf(Account::class, $result);
        $this->assertEquals($account->id, $result->id);
    }

    /** @test */
    public function test_it_throws_exception_when_account_not_found_by_id(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Account not found');
        $this->expectExceptionCode(404);

        $this->service->findByID(99999);
    }

    // ==================== verifyEmail ====================

    /** @test */
    public function test_it_verifies_email(): void
    {
        $account = Account::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        $this->service->verifyEmail('unverified@example.com');

        $account->refresh();
        $this->assertNotNull($account->email_verified_at);
    }

    /** @test */
    public function test_it_throws_exception_when_verifying_nonexistent_email(): void
    {
        $this->expectException(ServiceException::class);

        $this->service->verifyEmail('nonexistent@example.com');
    }

    // ==================== changePassword ====================

    /** @test */
    public function test_it_changes_password(): void
    {
        $account = Account::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->service->changePassword($account, 'new-password-123');

        $account->refresh();
        $this->assertTrue(Hash::check('new-password-123', $account->password));
        $this->assertFalse(Hash::check('old-password', $account->password));
    }

    // ==================== changeEmail ====================

    /** @test */
    public function test_it_changes_email_and_unverifies(): void
    {
        $account = Account::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->service->changeEmail($account, 'new@example.com');

        $account->refresh();
        $this->assertEquals('new@example.com', $account->email);
        $this->assertNull($account->email_verified_at);
    }

    // ==================== updateStatus ====================

    /** @test */
    public function test_it_updates_account_status(): void
    {
        $account = Account::factory()->create(['status' => 'active']);

        $this->service->updateStatus($account, 'desactivated');

        $account->refresh();
        $this->assertEquals('desactivated', $account->status);
    }

    // ==================== requestChangeReset ====================

    /** @test */
    public function test_it_allows_reset_request_for_active_account(): void
    {
        $account = Account::factory()->create(['status' => 'active']);

        // Should not throw exception
        $this->service->requestChangeReset($account->email);
        $this->assertTrue(true); // Pass if no exception
    }

    /** @test */
    public function test_it_throws_exception_when_blocked_account_requests_reset(): void
    {
        $account = Account::factory()->create(['status' => 'blocked']);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('The account related to this email is blocked');
        $this->expectExceptionCode(403);

        $this->service->requestChangeReset($account->email);
    }

    // ==================== checkIfBlocked ====================

    /** @test */
    public function test_it_passes_when_account_is_not_blocked(): void
    {
        $account = Account::factory()->create(['status' => 'active']);

        $this->service->checkIfBlocked($account);
        $this->assertTrue(true);
    }

    /** @test */
    public function test_it_throws_exception_when_account_is_blocked(): void
    {
        $account = Account::factory()->create(['status' => 'blocked']);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Your account is blocked');
        $this->expectExceptionCode(403);

        $this->service->checkIfBlocked($account);
    }

    // ==================== checkIfPending ====================

    /** @test */
    public function test_it_passes_when_provider_is_not_pending(): void
    {
        $account = Account::factory()->create(['status' => 'active']);

        $this->service->checkIfPending($account);
        $this->assertTrue(true);
    }

    /** @test */
    public function test_it_throws_exception_when_pending_provider(): void
    {
        $role =Role::factory()->create(['name'=>'provider']);
        $account = Account::factory()->create(['status'=>'pending']);
        $account->roles()->attach($role);
        dump($account);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Pending providers cannot activate or deactivate their account.');
        $this->expectExceptionCode(403);

        $this->service->checkIfPending($account);
    }

    /** @test */
    public function test_it_passes_when_not_a_provider(): void
    {
        $account = Account::factory()->create();// Even if pending

        $this->service->checkIfPending($account);
        $this->assertTrue(true);
    }

    // ==================== active ====================

    /** @test */
    public function test_it_activates_account(): void
    {
        $account = Account::factory()->create(['status' => 'desactivated']);

        $this->service->active($account);

        $account->refresh();
        $this->assertEquals('active', $account->status);
    }

    /** @test */
    public function test_it_throws_exception_when_activating_already_active_account(): void
    {
        $account = Account::factory()->create(['status' => 'active']);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('This account is already active');
        $this->expectExceptionCode(409);

        $this->service->active($account);
    }

    /** @test */
    public function test_it_throws_exception_when_activating_blocked_account(): void
    {
        $account = Account::factory()->create(['status' => 'blocked']);

        $this->expectException(ServiceException::class);
        $this->expectExceptionCode(403);

        $this->service->active($account);
    }

    // ==================== desactive ====================

    /** @test */
    public function test_it_deactivates_account(): void
    {
        $account = Account::factory()->create(['status' => 'active']);

        $this->service->desactive($account);

        $account->refresh();
        $this->assertEquals('desactivated', $account->status);
    }

    /** @test */
    public function test_it_throws_exception_when_deactivating_already_desactivated_account(): void
    {
        $account = Account::factory()->create(['status' => 'desactivated']);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('This account is already desactivated.');
        $this->expectExceptionCode(409);

        $this->service->desactive($account);
    }

    // ==================== blockAccount ====================

    /** @test */
    public function test_it_blocks_account(): void
    {
        $account = Account::factory()->create(['status' => 'active']);

        $this->service->blockAccount($account->id);

        $account->refresh();
        $this->assertEquals('blocked', $account->status);
    }

    /** @test */
    public function test_it_throws_exception_when_blocking_already_blocked_account(): void
    {
        $account = Account::factory()->create(['status' => 'blocked']);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Your account is blocked');
        $this->expectExceptionCode(403);

        $this->service->blockAccount($account->id);
    }
}

