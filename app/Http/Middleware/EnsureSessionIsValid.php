<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Session;
use App\Exceptions\ServiceException;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureSessionIsValid
{
    public function handle($request, Closure $next)
    {
        // 1. Get authenticated user
        $user = auth()->user();

        if (!$user) {
            throw new ServiceException('Unauthenticated', 401);
        }

        // 2. Extract JTI from token
        $payload = JWTAuth::parseToken()->getPayload();
        $jti = $payload->get('jti');

        if (!$jti) {
            throw new ServiceException('Invalid token (missing session)', 401);
        }

        // 3. Check session in DB
        $session = Session::find($jti);

        if (!$session || $session->revoked_at) {
            throw new ServiceException('Session revoked', 401);
        }

        // 4. Check Redis (session still active)
        $redisKey = $this->redisKey($user->id);

        if (Redis::zscore($redisKey, $jti) === null) {
            // Sync DB if Redis says it's gone
            $session->update(['revoked_at' => now()]);

            throw new ServiceException('Session expired', 401);
        }

        // 5. (Optional but recommended) Update activity
        $this->touchSession($session, $redisKey, $jti);

        return $next($request);
    }

    private function touchSession($session, $redisKey, $jti)
    {
        // Update Redis activity
        Redis::zadd($redisKey, now()->timestamp, $jti);

        // Only update DB every 5 minutes
        if (!$session->last_used_at || $session->last_used_at->lt(now()->subMinutes(5))) {
            $session->update([
                'last_used_at' => now()
            ]);
        }
    }

    private function redisKey($accountId)
    {
        return "user_sessions:{$accountId}";
    }
}