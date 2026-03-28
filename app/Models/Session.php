<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasUuids;
    protected $fillable=[
        'account_id',
        'revoked_at',
        'last_used_at',
        'ip_address',
        'user_agent'
    ];

    public function account(){
        return $this->belongsTo(Account::class);
    }
     public function refresh_tokens(){
        return $this->hasMany(RefreshToken::class);
    }
 

    public function revoke(){
         if ($this->revoked_at) {
            return;
        }

        $this->update([
            'revoked_at' => now(),
        ]);

        $this->refresh_tokens()->update([
            'revoked' => true,
        ]);
    }

    

 
}
