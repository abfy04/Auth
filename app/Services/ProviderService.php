<?php 
namespace App\Services;

use App\Http\Resources\ProviderResource;
use App\Jobs\SendEmailJob;
use App\Mail\YouAreApprovedMail;
use App\Models\Account;
use App\Models\Provider;
use App\Models\Role;
use App\Mail\YouAreRejected;

use Illuminate\Support\Facades\Cache;
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
                'city' => $validatedData['city'],
                'status'=>'pending'
            ]);

            return $provider->load('account');
        });

    }

    public function updateProvider($account,$validatedData){
         $provider = $account->provider;
         $provider->update($validatedData);
         return new ProviderResource($provider);
    }


    public function updateStatus($id,$status){
        $providerAccount = Account::where('id',$id)->first();
        // Prevent duplicate email
        $this->isProviderValid($providerAccount,$status);

        $providerAccount->provider->update([
            'status'=>$status,
            'approved_by'=>$status == 'approved'? auth()->user()->id : null,
            'approved_at'=>$status == 'approved'? now() : null
        ]);
        $this->sendEmailBasedOnStatus($status,$$providerAccount->provider,$providerAccount->email);
    }
       public function sendEmailBasedOnStatus($status,$provider,$email){
        if($status =='approved'){
                SendEmailJob::dispatch(
                    new YouAreApprovedMail($provider->business_name),
                    $email
                );
            return ;
        }
        if($status =='rejected'){
                SendEmailJob::dispatch(
                    new YouAreRejected($provider->business_name),
                    $email
                );
            return ;
        }
    }
    public function isProviderValid($providerAccount,$status){
        // Prevent duplicate email
        if (! $providerAccount) {
            throw new ServiceException('Provider not found ', 404);
        }
        if ($providerAccount->status == 'blocked') {
            throw new ServiceException('This provider is blocked ', 409);
        }
        if ($providerAccount->provider->status == $status) {
            throw new ServiceException("This provider is already {$status} ", 409);
        }
    }

    public function getApprovedProviders($request){
        $perPage = min($request->input('per_page', 10), 50);
        $cacheKey = 'pprovedProviders:' . http_build_query($request->all());
        return Cache::remember($cacheKey,now()->addMinutes(10),function() use($request,$perPage){
             $query = Provider::query()
             ->where('status','apprved')
             ->whereHas('account', function ($q) {
                    $q->where('status', 'active'); // approved providers
            });

            // Filter by city
            if ($request->filled('city')) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

            // Pagination
            $providers = $query->paginate($perPage);

            return ProviderResource::collection($providers);

        });
       
    }
    public function getProviders($request){
        
        $perPage = min($request->input('per_page', 10), 50);
        $cacheKey = 'providers:' . http_build_query($request->all());
        
        return Cache::remember($cacheKey,now()->addMinutes(10),function()use($request,$perPage){
            $query = Provider::query()->with('account');
             // 🔹 Filter by status
            if ($request->filled('status')) {
                $query->whereHas('account', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            // 🔹 Filter by city
            if ($request->filled('city')) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

        

            return ProviderResource::collection(
                $query->paginate($perPage)
            );
        });
       
    }

 
}