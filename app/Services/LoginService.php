<?php

use App\Models\RefreshToken;
use App\Exceptions\ServiceException;
use App\Models\Audit;
use App\Models\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginService
{
    public function login($credentials, $userAgent, $ip)
    {
        $this->authenticate($credentials);

        $account = auth()->user();
        $this->ensureVerified($account);

        return DB::transaction(function () use ($account, $userAgent, $ip) {

            $session = $this->createSession($account, $userAgent, $ip);

            $this->storeSessionInRedis($account->id, $session->id);

            $this->enforceSessionLimit($account->id);

            $accessToken = $this->createAccessToken($account, $session->id);

            $refreshToken = $this->createRefreshToken($account, $session, $userAgent, $ip);

            $this->audit($account, $userAgent, $ip);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => auth()->factory()->getTTL() * 60
            ];
        });
    }

    private function authenticate($credentials)
    {
        if (!auth()->attempt($credentials)) {
            throw new ServiceException('Invalid credentials', 401);
        }
    }

    private function ensureVerified($account)
    {
        if (!$account->isVerified()) {
            auth()->logout();
            throw new ServiceException('Email not verified', 403);
        }
    }

    private function createSession($account, $userAgent, $ip)
    {
        return Session::create([
            'id' => (string) Str::uuid(),
            'account_id' => $account->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'last_used_at' => now(),
        ]);
    }

    private function storeSessionInRedis($accountId, $sessionId)
    {
        $key = $this->redisKey($accountId);

        Redis::zadd($key, now()->timestamp, $sessionId);
        Redis::expire($key, 60 * 60 * 24 * 30);
    }

    private function enforceSessionLimit($accountId)
    {
        $key = $this->redisKey($accountId);
        $count = Redis::zcard($key);

        if ($count <= 3) return;

        $oldSessions = Redis::zrange($key, 0, $count - 4);

        foreach ($oldSessions as $oldJti) {
            Redis::zrem($key, $oldJti);

            Session::where('id', $oldJti)->update([
                'revoked_at' => now()
            ]);
        }
    }

    private function createAccessToken($account, $jti)
    {
        return JWTAuth::claims(['jti' => $jti])->fromUser($account);
    }

    private function createRefreshToken($account, $session, $userAgent, $ip)
    {
        return RefreshToken::generateForSession(
            $account->id,
            $session->id,
            $userAgent,
            $ip,
            60 * 24 * 30
        );
    }

    private function audit($account, $userAgent, $ip)
    {
        Audit::log(
            $account->id,
            'login',
            'User logged in',
            null,
            true,
            null,
            $userAgent,
            $ip
        );
    }

    private function redisKey($accountId)
    {
        return "user_sessions:{$accountId}";
    }
}