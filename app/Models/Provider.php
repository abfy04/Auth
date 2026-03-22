<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Provider extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = ['business_name', 'account_id', 'city','approved_by'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

}
