<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Account extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;
    use HasUuids;
    

    protected $fillable = [
        'status', 
        'email', 
        'password',
        'email_verified_at',
        'blocked_by',
        'block_reason',
        'email_changed_at',
        'blocked_at'
    ];
    protected $hidden = ['password'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'account_roles');
    }
    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function provider(){
        return $this->hasOne(Provider::class);
    }

    public function isProvider(){
        return $this->roles->pluck('name')->contains('provider');
    }

    public function isBlocked(){
        return $this->status === "blocked";
    }
    public function isVerified(){
        return $this->email_verified_at;
    }

    public function sessions(){
        return $this->hasMany(Session::class);
    }

    public function refresh_tokens(){
        return $this->hasMany(RefreshToken::class);
    }

    public function revokeAllSessions(): void
    {
        $this->sessions()
            ->whereNull('revoked_at')
            ->get()
            ->each
            ->revoke();
    }

    public function emailChangedRecently(){
        return $this->email_changed_at && $this->email_changed_at < Carbon::now()->subDays(14);
    }
  
}
