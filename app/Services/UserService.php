<?php
namespace App\Services;

use App\Models\Account;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ServiceException;
use App\Models\Role;


class UserService
{
    public function createUser($validatedData)
    {
         return DB::transaction(function () use ($validatedData ) {

            // Prevent duplicate email
            if (Account::where('email', $validatedData['email'])->exists()) {
                throw new ServiceException('Email already exists', 409);
            }

            // Create account
            $account = Account::create([
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'status' => 'active'
            ]);

            //role assignement
             
            $role = Role::where('name', 'provider')->first();
            if (!$role) {
                throw new ServiceException('Role not found');
            }
            $account->roles()->attach($role->id);

            // Create user profile
            $user = $account->user()->create([
                'name' => $validatedData['name'],
                'birthdate' => $validatedData['birthdate'],
            ]);

            return $user->load('account');
        });
    }
}