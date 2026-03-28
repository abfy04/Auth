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
        $reason = $request->validate(['reason'=>"required|string|min:3"]);
        $admin = auth()->user();
        $this->accountService->blockAccount($id,$reason);

        return ApiResponse::success('User is blocked successfully',200);   
        
    }

    
    public function toggleActivation(Request $request){
        $status = $request->validate(['status'=>'required|string|in:active,desactive']);
        $account = auth()->user();
        if(!$account){
             return ApiResponse::error('Account Not Found', 404);
        }
        if ($status === 'active') {
            $this->accountService->toggleAccountStatus($account,'active');
            return ApiResponse::success('Account is active now ', 200);
            
        }
        $this->accountService->toggleAccountStatus($account,'inactive');
        return ApiResponse::success('Account is inactive now', 200);
    }
}
