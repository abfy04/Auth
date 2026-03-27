<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use App\Services\UserService;
use Illuminate\Http\Request;


class UserController extends Controller
{
    public function update(UpdateUserRequest $request,UserService $userService){
     
        $account = auth()->user();
        if (!$account->user) {
            return ApiResponse::error('User not found', 404);
        }
        $validatedData=$request->validated();

        
        $user =$userService->updateUser($account,$validatedData);

        return ApiResponse::success(
            'Info Updated Successfully',
            200 , 
            $user
        );
          
    }

     public function index(Request $request,UserService $userService)
    {
        $users = $userService->getUsers($request);

        return ApiResponse::success(
            'fetched successfully',
            200,
            $users
        );

    }
}
