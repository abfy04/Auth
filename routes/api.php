<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HealthController;

Route::prefix('v1')->group(function(){
    Route::prefix('health')->group(function(){
        Route::get('live',[HealthController::class,'alive']);
        Route::get('ready',[HealthController::class,'ready']);
    });
    Route::post('login', [AuthController::class, 'login']);
   
    Route::post('users', [RegisterController::class, 'userRegister']);
    Route::post('providers', [RegisterController::class, 'providerRegister']);
    
    Route::post('email/verify', [OtpController::class, 'verifyEmail']);
        //forget password routes
    Route::post('password/forget', [OtpController::class, 'requestPasswordReset']);
    Route::post('reset-code/verify', [OtpController::class, 'verifyPasswordResetOpt']);
    Route::post('password/reset', [PasswordController::class, 'resetPassword']);

    Route::middleware(['auth:api','verified','notBlocked','isSessionValid'])->group(function () {

        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout/all', [AuthController::class, 'logoutAll']);
        Route::post('refresh', [AuthController::class, 'refresh']);

        Route::prefix('account')->group(function(){

            Route::patch('status', [AccountController::class, 'toggleActivation']);

            Route::middleware(['active'])->group(function () {
                Route::get('/', [AuthController::class, 'profile']);
                Route::patch('password', [PasswordController::class, 'changePassword']);
                Route::patch('email', [OtpController::class, 'requestChangeEmail']);
            });

        });
        Route::middleware(['active'])->group(function () {

            Route::middleware(['checkRole:provider','approved'])->group(function(){

                Route::patch('providers/me',[ProviderController::class,'update']);

            });
            
            Route::middleware(['checkRole:user|admin'])->group(function(){

                Route::patch('users/me',[UserController::class,'update']);

            });
            Route::middleware(['checkRole:user'])->group(function(){

                Route::patch('providers/approved',[ProviderController::class,'getApprovedProviders']);

            });

            Route::middleware('checkRole:admin')->group(function () {   

                Route::patch('providers/{id}', [ProviderController::class, 'approve']);
                Route::get('providers/', [ProviderController::class, 'index']);
                Route::get('users/', [UserController::class, 'index']);

                Route::patch('accounts/{id}', [AccountController::class, 'block']);  

            });
        });   
    });
});

    





//

