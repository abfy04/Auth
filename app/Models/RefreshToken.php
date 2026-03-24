<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;

class RefreshToken extends Model
{
    use HasUuids;
   protected $table = 'refresh_tokens';

    protected $fillable = [
        'account_id',
        'token',
        'revoked',
        'device',
        'ip',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // Relationships
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // Generate a new token string (hashed for DB)
    public static function generateForAccount($accountId, $device = null, $ip = null, int $ttlMinutes = 43200) // 30 days default
    {
        $rawToken = Str::random(64); // send raw token to client
        $hashedToken = Hash::make($rawToken);

        $refreshToken = self::create([
            'account_id' => $accountId,
            'token' => $hashedToken,
            'revoked' => false,
            'device' => $device,
            'ip' => $ip,
            'expires_at' => Carbon::now()->addMinutes($ttlMinutes),
        ]);

        return [$refreshToken, $rawToken]; // store hashed in DB, send raw to client
    }

    // Verify token
    public function verify(string $token): bool
    {
        return ! $this->revoked && ! $this->isExpired() && Hash::check($token, $this->token);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    // Revoke token
    public function revoke(): void
    {
        $this->revoked = true;
        $this->save();
    }

    // Update last used timestamp
    public function markUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }
}
