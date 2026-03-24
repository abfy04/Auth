<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Account;
class Audit extends Model
{
    use HasUuids;

    protected $table = 'audits';

    protected $fillable = [
        'account_id',
        'action',
        'description',
        'target_type',
        'target_id',
        'device',
        'ip',
        'success',
        'error',
    ];

    protected $casts = [
        'success' => 'boolean',
        'error' => 'array', // store structured error info as JSON
    ];

    // Relationships
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function target()
    {
        return $this->morphTo();
    }

    // Helper method to create audit logs
    public static function log(
        string $accountId,
        string $action,
        string|null $description = null,
        ?Model $target = null,
        bool $success = true,
        array|string|null $error = null,
        ?string $device = null,
        ?string $ip = null
    ) {
        return self::create([
            'account_id' => $accountId,
            'action' => $action,
            'description' =>  $description,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->id,
            'success' => $success,
            'error' => $error,
            'device' => $device,
            'ip' => $ip,
        ]);
    }
}
