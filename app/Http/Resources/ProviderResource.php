<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'business_name'=>$this->business_name,
            'city'=>$this->city,
            'status' =>$this->status,
            'account' =>[
                'status'=>$this->account->status,
                'email'=>$this->account->email,
                
            ],
        ];
    }
}
