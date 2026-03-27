<?php
namespace App\Services;

use App\Models\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ServiceException;
use App\Models\Role;
use App\Http\Resources\UserResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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
             
            $role = Role::where('name', 'user')->first();
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

    public function updateUser($account,$validatedData){
        $user = $account->user;
        $user->update($validatedData);

        return  new UserResource($user);
    }

    public function getUsers($request){
        $perPage = min($request->input('per_page', 10), 50);
        $cacheKey = 'users:' . http_build_query($request->all());
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request, $perPage) {

            $query = User::query()->with('account');

            // 🔹 Filter by city
            $query->when($request->filled('city'), function ($q) use ($request) {
                $q->where('city', 'like', '%' . $request->city . '%');
            });

            // 🔹 Filter by age range
            $query->when(
                $request->filled('min_age') || $request->filled('max_age'),
                function ($q) use ($request) {

                    $q->where(function ($q2) use ($request) {

                        if ($request->filled('min_age')) {
                            $q2->where(
                                'birthdate',
                                '<=',
                                Carbon::now()->subYears($request->min_age)
                            );
                        }

                        if ($request->filled('max_age')) {
                            $q2->where(
                                'birthdate',
                                '>=',
                                Carbon::now()->subYears($request->max_age)
                            );
                        }

                    });
                }
            );

            return UserResource::collection(
                $query->paginate($perPage)
            );
        });

    
    }
}