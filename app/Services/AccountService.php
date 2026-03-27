<?php
namespace App\Services;

use App\Exceptions\ServiceException;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
class AccountService
{

    public function findByEmail($email)
    {
        $account  = Account::where('email', $email)->first();
        if(!$account){
            throw new ServiceException('Account not found',404);
        }
        return $account;
    }
     public function findByID($id)
    {
        $account  = Account::where('id',$id)->first();
        if(!$account){
            throw new ServiceException('Account not found',404);
        }
        return $account;
    }
 

    public function verifyEmail($email)
    {
        $account = $this->findByEmail($email);
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

    public function updateStatus($account,$newStatus){
        $account->update(['status'=>$newStatus]);
    }
    
    public function requestChangeReset($email){
        $account = $this->findByEmail($email);
        if ($account->status =="blocked"){
            throw new   ServiceException('The account related to this email is blocked',403);
        }
    }

    public function checkIfBlocked($account){
      
        if ($account->isBlocked()) {
            throw new ServiceException('Your account is blocked', 403);
        }
        
    }

     public function checkIfPending($account){
        if ($account->isProvider() && $account->isPending()) {
            throw new ServiceException('Pending providers cannot activate or deactivate their account.', 403);
        }
        
    }
    public function active($account)
    {
        $this->checkIfBlocked($account); 
        $this->checkIfPending($account);

        // Toggle active/desactivated
        if ($account->status === 'active') {
            throw new ServiceException('This account is already active.', 409);
        }

        $account->update(['status'=>'active']);

    }

    public function desactive($account)
    {
        $this->checkIfBlocked($account); 
        $this->checkIfPending($account);

        // Toggle active/desactivated
        if ($account->status === 'desactivated') {
            throw new ServiceException('This account is already desactivated.', 409);
        }

        $account->update(['status'=>'desactivated']);

    }

    public function blockAccount($id){
        $user = $this->findByID($id);
        $this->checkIfBlocked($user);
        $user->update(['status'=>'blocked']);
    }


}