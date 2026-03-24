<?php 
use App\Exceptions\ServiceException;
use App\Models\RefreshToken;
use App\Models\Audit;
class RefreshTokenService{
    public function refreshToken($rawToken,$userAgent,$ip){
            // Find the token in DB
        $refreshToken = RefreshToken::where('revoked', false)
            ->where('expires_at', '>', now())
            ->get() // get collection to check hash
            ->first(fn($rt) => Hash::check($rawToken, $rt->token));

        if (! $refreshToken) {
           throw new ServiceException('Invalid or expired refresh token', 401);
        }

        $account = $refreshToken->account;

        // Issue new access token
        $accessToken = auth()->login($account);

        // Update last used
        $refreshToken->markUsed();

        Audit::log($account->id, 'refresh_token', 'Refreshed access token', null, true, null, $userAgent, $ip);

        return [
                'access_token' => $accessToken,
                'expires_in' => auth()->factory()->getTTL() * 60
        ];
        
    }
}