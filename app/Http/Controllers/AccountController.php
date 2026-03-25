<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use App\Services\AccountService;

class AccountController extends Controller
{
    public $accountService;
    public function __construct(AccountService $accountService){
        $this->accountService = new AccountService();
    }

    public function block(Request $request , $id){
        $admin = auth()->user();
        $this->accountService->blockAccount($id);

        return ApiResponse::success('User is blocked successfully',200);   
        
    }

    public function active($account)
    {
        $this->accountService->active($account);

        // Fallback for unexpected statuses
        return ApiResponse::success('Status changed successfuly', 200);
    }

     public function desactive($account)
    {
        
        $this->accountService->desactive($account);

        // Fallback for unexpected statuses
        return ApiResponse::success('Status changed successfuly', 200);
    } 
    
    public function toggleActivation(Request $request){
        $status = $request->validate(['status'=>'required|string|in:active,desactive']);
        $account = auth()->user();
        if(!$account){
             return ApiResponse::error('Account Not Found', 404);
        }
        if ($status === 'active') {
            $this->active($account);
        }
        $this->desactive($account);
    }
}
