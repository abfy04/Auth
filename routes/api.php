<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OptController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\PasswordController;

Route::prefix('auth')->group(function () {
    Route::post('register/user', [RegisterController::class, 'userRegister']);
    Route::post('register/provider', [RegisterController::class, 'porviderRegister']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-email', [OptController::class, 'verifyEmail']);
    //forget password routes
    Route::post('forget-password', [PasswordController::class, 'forgetPassword']);
    Route::post('verify-reset-code', [OptController::class, 'verifyPasswordResetOpt']);
    Route::post('reset-password', [PasswordController::class, 'resetPassword']);

   
    

    // Route::middleware('auth:api')->group(function () {
    //     Route::post('profile', [AuthController::class, 'profile']);
    //     Route::post('logout', [AuthController::class, 'logout']);
    //     Route::post('refresh', [AuthController::class, 'refresh']);
    //     Route::patch('users', [AuthController::class, 'ChangePassword']);
    //     Route::patch('users', [UserController::class, 'ToggleActivation']);
    // });

    // Route::middleware('checkRole:admin')->group(function () {
    //     Route::post('profile', [AuthController::class, 'profile']);
    //     Route::patch('users', [UserController::class, 'ToggleBlockUser']);
    //     Route::patch('providers', [ProcessController::class, 'ApproveProvider']);
    //     Route::delete('users/{id}', [UserController::class, 'deleteUser']);
    //     Route::get('users', [UserController::class, 'getAllUsers']);
    //     Route::get('providers', [ProviderController::class, 'getAllProviders']);
    //     Route::delete('providers/{id}', [ProviderController::class, 'deleteProvider']);
    // });

    // Route::middleware('checkRole:provider')->group(function () {
    //     Route::post('profile', [AuthController::class, 'profile']);
    //     Route::post('providers', [ProviderController::class, 'createProvider']);
    //     Route::patch('providers', [ProviderController::class, 'updateProvider']);
    // });

    // Route::middleware('checkRole:user')->group(function () {
    //     Route::post('profile', [AuthController::class, 'profile']);
    //     Route::get('providers', [ProviderController::class, 'getApprovedProviders']);
    //     Route::patch('users', [UserController::class, 'UpdateProfile']);
    // });
    

});

