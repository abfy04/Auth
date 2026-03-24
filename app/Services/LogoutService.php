<?php
use App\Models\RefreshToken;
use App\Exceptions\ServiceException;
use App\Models\Audit;
class LogoutService {

    public function logout($userAgent,$ip)
    {
        $account = auth()->user();

        // Revoke all tokens for this device (optional: filter by User-Agent)
        RefreshToken::where('account_id', $account->id)
            ->where('device', $userAgent)
            ->update(['revoked' => true]);

        auth()->logout();

        Audit::log($account->id, 'logout', 'User logged out', null, true, null, $userAgent, $ip);

    }
}