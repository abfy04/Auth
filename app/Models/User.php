<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Model  
{
    use HasUuids;

    protected $fillable = ['name', 'birthdate', 'account_id'];

        public function account()
        {
            return $this->belongsTo(Account::class);
        }
        

}