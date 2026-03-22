<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\Account;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $adminRole = Role::where('name', 'admin')->first();
        $adminAccount = Account::create([
            'email' => 'fikryayoub24@gmail.com',
            'password'=> Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $adminAccount->roles()->attach($adminRole->id);
        User::create([
            'name' => 'Ayoub Fikry',
            'birthdate' => '1990-01-01',
            'account_id' => $adminAccount->id,
        ]);
    }
}
