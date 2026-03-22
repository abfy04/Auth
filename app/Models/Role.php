<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;
    use HasUuids;
 
    protected $fillable = ['name'];


    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_roles');
    }
  

}
