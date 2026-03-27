<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'status'=>$this->status,
            'email_verified_at'=> $this->email_verified_at,

            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),

            // dynamic profile
            'profile' => $this->when(true, function () {
                if ($this->relationLoaded('user') && $this->user) {
                    return [
                        'type' => 'user',
                        'name' => $this->user->name,
                        'birthdate' => $this->user->birthdate,
                    ];
                }

                if ($this->relationLoaded('provider') && $this->provider) {
                    return [
                        'type' => 'provider',
                        'business_name' => $this->provider->business_name,
                        'city' => $this->provider->city,                
                        'status'=>$this->provider->status,

                        
                    ];
                }

                return null;
            }),
        ];
    }
}