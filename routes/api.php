<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\UserController;

Route::prefix('v1')->group(function(){
    Route::post('login', [AuthController::class, 'login']);
   
    Route::post('users', [RegisterController::class, 'userRegister']);
    Route::post('providers', [RegisterController::class, 'providerRegister']);
    
    Route::post('email/verify', [OtpController::class, 'verifyEmail']);
        //forget password routes
    Route::post('password/forget', [OtpController::class, 'requestPasswordReset']);
    Route::post('reset-code/verify', [OtpController::class, 'verifyPasswordResetOpt']);
    Route::post('password/reset', [PasswordController::class, 'resetPassword']);

    Route::middleware('auth:api')->group(function () {

        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);

        Route::get('users/me', [AuthController::class, 'profile']);
        Route::patch('users/me/password/change', [PasswordController::class, 'changePassword']);
        Route::patch('users/me/email/change', [OtpController::class, 'requestChangeEmail']);

       
        // Route::patch('users/me/status', [AccountController::class, 'toggleActivation']);

        Route::middleware('checkRole:provider')->group(function(){
            Route::patch('providers/',[ProviderController::class,'update']);
        });
       
        Route::middleware('checkRole:user|admin')->group(function(){
            Route::patch('users/me',[UserController::class,'update']);
        });

        Route::middleware('checkRole:admin')->group(function () {       
            Route::patch('providers/{id}', [ProviderController::class, 'approve']);   
        });
   
    });
});

    





//

