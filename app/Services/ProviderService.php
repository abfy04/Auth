<?php 
namespace App\Services;

use App\Models\Account;
use App\Models\Provider;
use App\Models\Role;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ServiceException;
class ProviderService
{
    public function createProvider($validatedData)
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
                'status' => 'pending'
            ]);

            //role assignement
            $role = Role::where('name', 'provider')->first();
            if (!$role) {
                throw new ServiceException('Role not found');
            }
            $account->roles()->attach($role->id);


            // Create user profile
            $provider = $account->provider()->create([
                'business_name' => $validatedData['business_name'],
                'city' => $validatedData['business_name'],
            ]);

            return $provider->load('account');
        });

    }
}