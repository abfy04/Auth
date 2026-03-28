<?php

use App\Models\RefreshToken;
use App\Models\Session;
use App\Models\Audit;
use App\Exceptions\ServiceException;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutService
{
    public function logout($userAgent, $ip)
    {
        $account = auth()->user();

        if (!$account) {
            throw new ServiceException('Unauthenticated', 401);
        }

        // 1. Extract session ID (JTI)
        $payload = JWTAuth::parseToken()->getPayload();
        $jti = $payload->get('jti');

        if (!$jti) {
            throw new ServiceException('Invalid token', 401);
        }

        $redisKey = $this->redisKey($account->id);

        // 2. Revoke session
        Session::where('id', $jti)->update([
            'revoked_at' => now()
        ]);

        // 3. Revoke all refresh tokens for this session
        RefreshToken::where('session_id', $jti)->update([
            'revoked' => true
        ]);

        // 4. Remove from Redis (instant invalidation)
        Redis::zrem($redisKey, $jti);

        // 5. Invalidate JWT
        auth()->logout();

        $this->audit($account,'logout','User logged out (current session)',$userAgent,$ip);
        // 6. Audit
    }

 

    public function logoutAll($userAgent, $ip)
    {
        $account = auth()->user();

        $redisKey = $this->redisKey($account->id);

        // 1. Get all sessions from Redis
        $sessions = Redis::zrange($redisKey, 0, -1);

        // 2. Revoke all in DB
        Session::where('account_id', $account->id)->update([
            'revoked_at' => now()
        ]);

        RefreshToken::where('account_id', $account->id)->update([
            'revoked' => true
        ]);

        // 3. Clear Redis بالكامل
        Redis::del($redisKey);

        // 4. Logout current JWT
        auth()->logout();

        $this->audit($account,'logout_all','User logged out from all sessions',$userAgent,$ip);
    }

    private function audit($account , $action,$desc,$userAgent,$ip){
        Audit::log(
            $account->id,
            $action,
            $desc,
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