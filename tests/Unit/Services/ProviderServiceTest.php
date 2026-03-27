<?php
// tests/Unit/Services/ProviderServiceTest.php

namespace Tests\Unit\Services;

use App\Exceptions\ServiceException;
use App\Http\Resources\ProviderResource;
use App\Models\Account;
use App\Models\Provider;
use App\Models\Role;
use App\Services\ProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ProviderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProviderService $service;
    protected Role $providerRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProviderService();
        
        // Create provider role once for all tests
        $this->providerRole = Role::create(['name' => 'provider']);
    }

    // ==================== createProvider ====================

    /** @test */
    public function test_it_creates_provider_with_account_and_role(): void
    {
        $data = [
            'email' => 'new@example.com',
            'password' => 'SecurePass123!',
            'business_name' => 'Test Business',
            'city' => 'New York',
        ];

        $result = $this->service->createProvider($data);

        // Assert provider returned
        $this->assertInstanceOf(Provider::class, $result);
        $this->assertEquals('Test Business', $result->business_name);
        $this->assertEquals('New York', $result->city);

        // Assert account created
        $this->assertNotNull($result->account);
        $this->assertEquals('new@example.com', $result->account->email);
        $this->assertEquals('pending', $result->account->status);
        $this->assertTrue(Hash::check('SecurePass123!', $result->account->password));

        // Assert role attached
        $this->assertTrue($result->account->roles->contains($this->providerRole));
    }

    /** @test */
    public function test_it_throws_exception_when_email_already_exists(): void
    {
        Account::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!',
            'business_name' => 'Test Business',
            'city' => 'New York',
        ];

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Email already exists');
        $this->expectExceptionCode(409);

        $this->service->createProvider($data);
    }

    /** @test */
    public function test_it_throws_exception_when_role_not_found(): void
    {
        // Delete the provider role to simulate missing role
        $this->providerRole->delete();

        $data = [
            'email' => 'norole@example.com',
            'password' => 'SecurePass123!',
            'business_name' => 'Test Business',
            'city' => 'New York',
        ];

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Role not found');

        $this->service->createProvider($data);
    }

    /** @test */
    public function test_it_rolls_back_transaction_on_error(): void
    {
        $data = [
            'email' => 'rollback@example.com',
            'password' => 'SecurePass123!',
            'business_name' => 'Test Business',
            'city' => 'New York',
        ];

        // First create succeeds
        $this->service->createProvider($data);

        // Second create with same email should fail AND not leave partial data
        try {
            $this->service->createProvider($data);
        } catch (ServiceException $e) {
            // Expected
        }

        // Assert only ONE account exists (transaction rolled back)
        $this->assertEquals(1, Account::where('email', 'rollback@example.com')->count());
    }

    // ==================== updateProvider ====================

    /** @test */
    public function test_it_updates_provider_data(): void
    {
        $account = Account::factory()->create(['status' => 'pending']);
        $account->roles()->attach($this->providerRole);
        $provider = $account->provider()->create([
            'business_name' => 'Old Name',
            'city' => 'Old City',
        ]);

        $updateData = [
            'business_name' => 'New Name',
            'city' => 'New City',
        ];

        $result = $this->service->updateProvider($account, $updateData);

        // Assert ProviderResource returned
        $this->assertInstanceOf(ProviderResource::class, $result);

        // Assert database updated
        $provider->refresh();
        $this->assertEquals('New Name', $provider->business_name);
        $this->assertEquals('New City', $provider->city);
    }

    /** @test */
    public function test_it_updates_only_provided_fields(): void
    {
        $account = Account::factory()->create(['status' => 'pending']);
        $account->roles()->attach($this->providerRole);
        $provider = $account->provider()->create([
            'business_name' => 'Original Name',
            'city' => 'Original City'
        ]);

        // Update only city
        $this->service->updateProvider($account, ['city' => 'Updated City']);

        $provider->refresh();
        $this->assertEquals('Original Name', $provider->business_name); // Unchanged
        $this->assertEquals('Updated City', $provider->city); // Changed
    }

    // ==================== approve ====================

    /** @test */
    public function test_it_approves_pending_provider(): void
    {
        // Mock authenticated admin user
        $admin = Account::factory()->create(['email' => 'admin@example.com']);
        Auth::shouldReceive('user')->andReturn($admin);

        $account = Account::factory()->create(['status' => 'pending']);
        $account->roles()->attach($this->providerRole);
        $provider = $account->provider()->create(['business_name' => 'To Approve','city'=>'DAKHLA']);

        $this->service->approve($account->id);

        // Assert account status changed
        $account->refresh();
        $this->assertEquals('active', $account->status);

        // Assert approved_by set
        $provider->refresh();
        $this->assertEquals($admin->id, $provider->approved_by);
    }

    /** @test */
    public function test_it_throws_exception_when_provider_not_found(): void
    {
        Auth::shouldReceive('user')->andReturn(Account::factory()->make());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Provider not found');
        $this->expectExceptionCode(404);

        $this->service->approve(99999);
    }

    /** @test */
    public function test_it_throws_exception_when_approving_blocked_provider(): void
    {
        Auth::shouldReceive('user')->andReturn(Account::factory()->make());

        $account = Account::factory()->create(['status' => 'blocked']);
        $account->roles()->attach($this->providerRole);
        $account->provider()->create(['business_name'=>'d1','city'=>'Paris']);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('This provider is blocked');
        $this->expectExceptionCode(409);

        $this->service->approve($account->id);
    }

    /** @test */
    public function test_it_throws_exception_when_approving_already_active_provider(): void
    {
        Auth::shouldReceive('user')->andReturn(Account::factory()->make());

        $account = Account::factory()->create(['status' => 'active']);
        $account->roles()->attach($this->providerRole);
        $account->provider()->create(['business_name'=>'d1','city'=>'Paris']);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('This provider is already approved');
        $this->expectExceptionCode(409);

        $this->service->approve($account->id);
    }

    // ==================== getApprovedProviders ====================

    /** @test */
    public function test_it_returns_approved_providers_from_cache(): void
    {
        // Create approved providers
        $approved = Account::factory()->create(['status' => 'active']);
        $approved->roles()->attach($this->providerRole);
        $approved->provider()->create(['business_name' => 'Approved Biz', 'city' => 'Paris']);

        $pending = Account::factory()->create(['status' => 'pending']);
        $pending->roles()->attach($this->providerRole);
        $pending->provider()->create(['business_name' => 'Pending Biz', 'city' => 'Paris']);

        // Mock cache to return pre-built response
        $expected = ProviderResource::collection(Provider::paginate(10));
        Cache::shouldReceive('remember')
            ->withArgs(function ($key, $ttl, $callback) {
                return str_starts_with($key, 'pprovedProviders:');
            })
            ->andReturn($expected);

        $request = Request::create('/api/providers/approved', 'GET', ['per_page' => 10]);
        $result = $this->service->getApprovedProviders($request);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result->resource);
    }

    /** @test */
    public function test_it_filters_approved_providers_by_city(): void
    {
        $account1 = Account::factory()->create(['status' => 'active']);
        $account1->roles()->attach($this->providerRole);
        $account1->provider()->create(['business_name'=>"Dakha",'city' => 'Paris']);

        $account2 = Account::factory()->create(['status' => 'active']);
        $account2->roles()->attach($this->providerRole);
        $account2->provider()->create(['business_name'=>"Dakhal",'city' => 'London']);

        // Mock cache
        Cache::shouldReceive('remember')
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback(); // Execute callback for real query
            });

        $request = Request::create('/api/providers/approved', 'GET', ['city' => 'Par']);
        $result = $this->service->getApprovedProviders($request);

        // Should only include Paris provider
        $this->assertEquals(1, $result->resource->count());
        $this->assertEquals('Paris', $result->resource->first()->city);
    }

    /** @test */
    public function test_it_respects_per_page_limit(): void
    {
        // Create 15 approved providers
        for ($i = 0; $i < 15; $i++) {
            $account = Account::factory()->create(['status' => 'active']);
            $account->roles()->attach($this->providerRole);
            $account->provider()->create(['business_name'=>"Dakhal{$i}",'city' => "City {$i}"]);
        }

        Cache::shouldReceive('remember')->andReturnUsing(fn($k, $t, $cb) => $cb());

        // Request 5 per page (under max of 50)
        $request = Request::create('/api/providers/approved', 'GET', ['per_page' => 5]);
        $result = $this->service->getApprovedProviders($request);

        $this->assertEquals(5, $result->resource->count());
        $this->assertEquals(15, $result->total());
    }

    /** @test */
    public function test_it_caps_per_page_at_50(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $account = Account::factory()->create(['status' => 'active']);
            $account->roles()->attach($this->providerRole);
            $account->provider()->create(['business_name'=>"d{$i}",'city'=>"P{$i}"]);
        }

        Cache::shouldReceive('remember')->andReturnUsing(fn($k, $t, $cb) => $cb());

        // Request 100 per page (should cap at 50)
        $request = Request::create('/api/providers/approved', 'GET', ['per_page' => 100]);
        $result = $this->service->getApprovedProviders($request);

        $this->assertEquals(50, $result->resource->count());
    }

    // ==================== getProviders ====================

    /** @test */
    public function test_it_returns_all_providers_with_account(): void
    {
        $account = Account::factory()->create(['status' => 'pending']);
        $account->roles()->attach($this->providerRole);
        $provider = $account->provider()->create(['business_name' => 'Test','city'=>"Dakhal"]);

        Cache::shouldReceive('remember')
            ->withArgs(fn($key) => str_starts_with($key, 'providers:'))
            ->andReturnUsing(fn($k, $t, $cb) => $cb());

        $request = Request::create('/api/providers', 'GET');
        $result = $this->service->getProviders($request);

        $this->assertInstanceOf(ProviderResource::class, $result->resource->first());
        $this->assertNotNull($result->resource->first()->account);
    }

    /** @test */
    public function test_it_filters_providers_by_status(): void
    {
        $active = Account::factory()->create(['status' => 'active']);
        $active->roles()->attach($this->providerRole);
        $active->provider()->create(['business_name' => 'Active','city'=>"Dakhal"]);

        $blocked = Account::factory()->create(['status' => 'blocked']);
        $blocked->roles()->attach($this->providerRole);
        $blocked->provider()->create(['business_name' => 'Blocked','city'=>"Dakhal"]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($k, $t, $cb) => $cb());

        $request = Request::create('/api/providers', 'GET', ['status' => 'active']);
        $result = $this->service->getProviders($request);

        $this->assertEquals(1, $result->resource->count());
        $this->assertEquals('Active', $result->resource->first()->business_name);
    }

    /** @test */
    public function test_it_filters_providers_by_city(): void
    {
        $paris = Account::factory()->create(['status' => 'active']);
        $paris->roles()->attach($this->providerRole);
        $paris->provider()->create(['business_name'=>'B1','city' => 'Paris']);

        $london = Account::factory()->create(['status' => 'active']);
        $london->roles()->attach($this->providerRole);
        $london->provider()->create(['business_name'=>'B2','city' => 'London']);

        Cache::shouldReceive('remember')->andReturnUsing(fn($k, $t, $cb) => $cb());

        $request = Request::create('/api/providers', 'GET', ['city' => 'Lon']);
        $result = $this->service->getProviders($request);

        $this->assertEquals(1, $result->resource->count());
        $this->assertEquals('London', $result->resource->first()->city);
    }

    /** @test */
    public function test_it_uses_cache_for_get_providers(): void
    {
        Cache::shouldReceive('remember')
            ->with(
                \Mockery::pattern('/^providers:/'),
                \Mockery::type(\Illuminate\Support\Carbon::class),
                \Mockery::type('callable')
            )
            ->once()
            ->andReturn(ProviderResource::collection(collect([])));

        $request = Request::create('/api/providers', 'GET');
        $this->service->getProviders($request);

        // If we get here, Cache::remember was called as expected
        $this->assertTrue(true);
    }

 
}