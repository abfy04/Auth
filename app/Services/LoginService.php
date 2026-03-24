<?php
use App\Models\RefreshToken;
use App\Exceptions\ServiceException;
use App\Models\Audit;
class LoginService {
    
    public function login ($credentials,$userAgent,$ip){
        if (! $token = auth()->attempt($credentials)) {
            throw new ServiceException('Invalid credentials', 401);
        }

        $account = auth()->user();

        if (! $account->email_verified_at) {
            auth()->logout();
            throw new ServiceException('Email not verified', 403);
        }

        // Create Refresh Token
        [$refreshToken, $rawToken] = RefreshToken::generateForAccount(
            $account->id,
            $userAgent,
            $ip,
            60 * 24 * 30 // 30 days TTL
        );

        // Audit login
        Audit::log($account->id, 'login', 'User logged in', null, true, null, $userAgent, $ip);

        return [
                'access_token' => $token,
                'refresh_token' => $rawToken,
                'expires_in' => auth()->factory()->getTTL() * 60 // seconds
        ];
        
    }
}