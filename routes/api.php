<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Landlord\PropertyController;
use App\Http\Controllers\Landlord\ReminderController;
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

    Route::controller(PropertyController::class)->group(function(){
        Route::get('/properties', 'index')->name('landlord.property.index');
        Route::post('/properties', 'store')->name('landlord.property.store');
        Route::get('/properties/{uuid}', 'show')->name('landlord.property.show');
        Route::post('/property-managers', 'store_manager')->name('landlord.propertManager.store');
        Route::get('/property-managers', 'property_managers')->name('landlord.propertyManager.index');
        Route::get('/property-managers/{uuid}', 'property_manager')->name('landlord.propertyManager.show');
        Route::post('/properties/{uuid}/units', 'store_unit')->name('lanldord.property.unit.store');
        Route::post('/properties-units/{uuid}/tenants', 'store_tenant')->name('landord.propertyUnit.tenant.store');
        Route::get('/tenants', 'tenants')->name('property.tenant.index');
        Route::post('/properties/{uuid}/units', 'store_unit')->name('property.unit.store');
        Route::put('/property-managers/{uuid}', 'update_manager')->name('property.manager.update');
        Route::put('/tenants/{uuid}', 'update_tenant')->name('property.tenant.update');
    });

    Route::controller(ReminderController::class)->group(function(){
        Route::post('/reminders', 'store')->name('landlord.reminder.store');
        Route::get('/reminders', 'index')->name('landlord.reminder.index');
        Route::get('/reminders/{uuid}', 'show')->name('landlord.reminder.show');
        Route::put('/reminders/{uuid}', 'update')->name('landlord.reminder.update');
        Route::delete('/reminders/{uuid}', 'destroy')->name("landlord.reminder.delete");
    });
});