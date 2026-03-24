<?php
namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Hash;
class AccountService
{

    public function findByEmail($email)
    {
        return Account::where('email', $email)->first();
    }

    public function verifyEmail($email)
    {
        $account = $this->findByEmail($email);
        if (!$account) {
            throw new \Exception('Account not found');
        }
        $account->email_verified_at = now();
        $account->save();
    }

    public function changePassword($account,$newPassword){
        $account->password = Hash::make($newPassword);
        $account->save();
    }

    public function changeEmail($account,$newEmail){
    
        $account->email = $newEmail;
        $account->email_verified_at = null; // Mark email as unverified
        $account->save();
    }
    
    public function requestChangeReset($email){
        $account = $this->findByEmail($email);
        if (!$account) {
            throw new \Exception('Account not found',404);
        }
        if ($account->status =="blocked"){
            throw new \Exception('The account related to this email is blocked',403);
        }
    }



}