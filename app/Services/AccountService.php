<?php
namespace App\Services;

use App\Exceptions\ServiceException;
use App\Jobs\SendEmailJob;
use App\Mail\PasswordChanged;
use App\Mail\YouAreBlocked;
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
        $account->revokeAllSessions();
        SendEmailJob::dispatch(new PasswordChanged(),$account->email);
    }

    public function changeEmail($account,$newEmail){
        if(!$account){
            throw new ServiceException('Accout not found',404);
        }
        if($account->emailChangedRecently()){
             throw new ServiceException('You cannot perform this action within 14 days of changing your email',403);
        }

        $account->email = $newEmail;
        $account->email_verified_at = null;
        $account->email_changed_at = now(); // Mark email as unverified
        $account->save();
        $account->revokeAllSessions();
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

    public function toggleAccountStatus($account,$status)
    {
        $this->checkIfBlocked($account); 
        $this->checkIfPending($account);
        if ($account->status === $status) {
            throw new ServiceException("This account is already {$status}", 409);
        }
        $account->update(['status'=> $status]);

    }


    public function blockAccount($id,$reason){
        $user = $this->findByID($id);
        $this->checkIfBlocked($user);
        $user->update([
            'status'=>'blocked',
            'blocked_at'=>now(),
            'block_reason'=>$reason
        ]);
        $user->revokeAllSessions();
        
        SendEmailJob::dispatch(new YouAreBlocked(),$user->email);
    }


}