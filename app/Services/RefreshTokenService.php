<?php

use App\Exceptions\ServiceException;
use App\Models\RefreshToken;
use App\Models\Session;
use App\Models\Audit;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RefreshTokenService
{
    public function refreshToken($rawToken, $userAgent, $ip)
    {
        return DB::transaction(function () use ($rawToken, $userAgent, $ip) {

            // 1. Retrieve token (indexed lookup)
            $refreshToken = $this->findToken($rawToken);

            // 2. Validate token state
            $this->validateToken($refreshToken, $rawToken);

            // 3. Validate session
            $session = $this->validateSession($refreshToken);

            $account = $session->account;
            $redisKey = $this->redisKey($account->id);
            $jti = $session->id;

            // 4. Validate Redis session
            $this->ensureSessionActiveInRedis($redisKey, $jti, $refreshToken, $session);

            // 5. Update session activity
            $this->updateSessionActivity($session, $redisKey, $jti, $userAgent, $ip);

            // 6. Generate tokens
            $accessToken = $this->createAccessToken($account, $jti);
            $newRefreshToken = $this->rotateRefreshToken($refreshToken, $session, $userAgent, $ip);

            // 7. Audit
            $this->audit($account, $userAgent, $ip);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'expires_in' => auth()->factory()->getTTL() * 60
            ];
        });
    }

    private function findToken($rawToken)
    {
        $tokenId = hash('sha256', $rawToken);

        return RefreshToken::where('token_id', $tokenId)
            ->lockForUpdate()
            ->first();
    }

    private function validateToken($refreshToken, $rawToken)
    {
        if (!$refreshToken || !Hash::check($rawToken, $refreshToken->token)) {
            throw new ServiceException('Invalid or expired refresh token', 401);
        }

        if ($refreshToken->expires_at <= now()) {
            throw new ServiceException('Refresh token expired', 401);
        }

        // 🚨 reuse detection
        if ($refreshToken->revoked) {
            $this->handleTokenReuse($refreshToken);
            throw new ServiceException('Token reuse detected', 401);
        }
    }

    private function validateSession($refreshToken)
    {
        $session = $refreshToken->session;

        if (!$session || $session->revoked_at) {
            $refreshToken->revoke();
            throw new ServiceException('Session revoked', 401);
        }

        return $session;
    }

    private function ensureSessionActiveInRedis($redisKey, $jti, $refreshToken, $session)
    {
        if (Redis::zscore($redisKey, $jti) === null) {
            $refreshToken->revoke();
            $session->update(['revoked_at' => now()]);

            throw new ServiceException('Session no longer active', 401);
        }
    }

    private function updateSessionActivity($session, $redisKey, $jti, $userAgent, $ip)
    {
        Redis::zadd($redisKey, now()->timestamp, $jti);
        Redis::expire($redisKey, 60 * 60 * 24 * 30);

        $session->update([
            'last_used_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    private function createAccessToken($account, $jti)
    {
        return JWTAuth::claims(['jti' => $jti])->fromUser($account);
    }

    private function rotateRefreshToken($refreshToken, $session, $userAgent, $ip)
    {
        $refreshToken->revoke();

        return RefreshToken::generateForSession(
            $session->account_id,
            $session->id,
            $userAgent,
            $ip,
            60 * 24 * 30
        );
    }

    private function handleTokenReuse($refreshToken)
    {
        $sessionId = $refreshToken->session_id;
        $accountId = $refreshToken->account_id;

        Session::where('id', $sessionId)->update([
            'revoked_at' => now()
        ]);

        RefreshToken::where('session_id', $sessionId)->update([
            'revoked' => true
        ]);

        Redis::zrem($this->redisKey($accountId), $sessionId);
    }

    private function audit($account, $userAgent, $ip)
    {
        Audit::log(
            $account->id,
            'refresh_token',
            'Refreshed access token',
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