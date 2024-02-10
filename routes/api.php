<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PharIo\Manifest\AuthorCollection;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(AuthController::class)->group(function(){
    Route::post('/signup', 'store')->name('user.signup');
    Route::post('/login', 'login')->name('user.login');
    Route::post('/activate', 'activate_account')->name('user.activateAccount');
    Route::get('/resend-activation-link', 'resend_activation_link')->name('user.resendAtivationLink');
    Route::post('/forgot-password', 'forgot_password')->name('user.forgotPassword');
    Route::post('/reset-password', 'reset_password')->name('user.resetPassword');
});

Route::middleware('auth:user-api')->group(function(){
    Route::controller(AuthController::class)->group(function(){
        Route::get('/me', 'me')->name('user.me');
    });
});