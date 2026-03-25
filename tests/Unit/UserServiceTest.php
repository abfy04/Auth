<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Models\Account;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\ServiceException;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    protected UserService $userService;

    protected function setUp():void
    {
        parent::setUp();
        $this->userService = new UserService();

    }
    /**
     * A basic unit test example.
     */
    public function test_it_creates_user_succesfully(): void
    {
        $role = Role::factory()->create(['name' => 'user']);
        $data = [
            "email"=>'smart.tv.fikry@gmail.com',
            "password"=>'password',
            "password_confirmation"=>'password',
            "name"=>'smart tv',
            "birthdate"=>'10-10-2021',
        ];
            // Act
        $user = $this->userService->createUser($data);

        // Assert
        $this->assertDatabaseHas('accounts', [
            'email' => 'smart.tv.fikry@gmail.com',
        ]);

        $this->assertEquals('smart tv', $user->name);
        $this->assertNotNull($user->account);
        $this->assertTrue(Hash::check('password', $user->account->password));
        
        $this->assertTrue(true);
    }

    public function test_create_user_fails_if_email_exists()
    {
         $role = Role::factory()->create(['name' => 'user']);

        Account::factory()->create([
            'email' => 'smart.tv.fikry@gmail.com',
            "password"=>'password'
        ]);

        $this->expectException(ServiceException::class);

        $data = [
            'email' => 'smart.tv.fikry@gmail.com',
            'password' => 'password123',
            "password_confirmation"=>'password123',
            'name' => 'John Doe',
            'birthdate' => '01-01-2001',
        ];

        // Act
        $this->userService->createUser($data);
    }

    public function test_create_user_fails_if_role_not_found()
    {
        // No role created

        $this->expectException(ServiceException::class);

        $data = [
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'name' => 'Test User',
            'birthdate' => '2000-01-01',
        ];

        $this->userService->createUser($data);
    }

    public function test_it_rolls_back_if_role_not_found()
    {
        $this->expectException(ServiceException::class);

        try {
            $this->userService->createUser([
                'email' => 'rollback@test.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'name' => 'Rollback User',
                'birthdate' => '2000-01-01',
            ]);
        } catch (\Exception $e) {

            $this->assertDatabaseMissing('accounts', [
                'email' => 'rollback@test.com',
            ]);

            throw $e;
        }
    }

    public function test_role_is_attached_to_account()
    {
        $role = Role::factory()->create(['name' => 'user']);

        $data = [
            'email' => 'role@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'name' => 'Role User',
            'birthdate' => '2000-01-01',
        ];

        $user = $this->userService->createUser($data);

        $this->assertDatabaseHas('account_roles', [
            'account_id' => $user->account->id,
            'role_id' => $role->id,
        ]);
    }

}
