<?php
namespace App\Services;

use App\Http\Requests\StoreAccountRequest;
use App\Models\Account;
use App\Models\Role;
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

    public function changeEmail(){

    }


}