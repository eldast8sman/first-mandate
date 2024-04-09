<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Landlord\NoticeController as LandlordNoticeController;
use App\Http\Controllers\Landlord\PropertyController;
use App\Http\Controllers\Landlord\ReminderController;
use App\Http\Controllers\Manager\NoticeController;
use App\Http\Controllers\Manager\PropertyController as ManagerPropertyController;
use App\Http\Controllers\Manager\ReminderController as ManagerReminderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Tenant\ApartmentController;
use App\Http\Controllers\Tenant\NoticeController as TenantNoticeController;
use App\Http\Controllers\Tenant\ReminderController as TenantReminderController;
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
    Route::post('/activate-from-addition', 'activate_from_addition')->name('user.activateFromAddition');
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
        Route::post('/property-units/{uuid}/tenants', 'store_tenant')->name('landord.propertyUnit.tenant.store');
        Route::get('/tenants', 'tenants')->name('property.tenant.index');
        Route::put('/property-managers/{uuid}', 'update_manager')->name('property.manager.update');
        Route::put('/tenants/{uuid}', 'update_tenant')->name('property.tenant.update');
    });

    Route::controller(ReminderController::class)->group(function(){
        Route::post('/reminders', 'store')->name('landlord.reminder.store');
        Route::post('/tenants/{uuid}/send-reminder', 'send_reminder');
        Route::get('/reminders', 'index')->name('landlord.reminder.index');
        Route::get('/reminders/{uuid}', 'show')->name('landlord.reminder.show');
        Route::put('/reminders/{uuid}', 'update')->name('landlord.reminder.update');
        Route::delete('/reminders/{uuid}', 'destroy')->name("landlord.reminder.delete");
    });

    Route::controller(LandlordNoticeController::class)->group(function(){
        Route::get('/notices', 'index');
        Route::post('/notices', 'send_notice');
    });

    Route::prefix('tenant')->group(function(){
        Route::controller(ApartmentController::class)->group(function(){
            Route::get('/apartments', 'index')->name('tenant.apartment.index');
            Route::post('/apartments', 'store')->name('tenant.apartment.store');
        });

        Route::controller(TenantReminderController::class)->group(function(){
            Route::post('/reminders', 'store')->name('tenant.reminder.store');
            Route::get('/reminders', 'index')->name('tenant.reminder.index');
            Route::get('/reminders/{uuid}', 'show')->name('tenant.reminder.show');
            Route::put('/reminders/{uuid}', 'update')->name('tenant.reminder.update');
            Route::delete('/reminders/{uuid}', 'destroy')->name("tenant.reminder.delete");
        });

        Route::controller(TenantNoticeController::class)->group(function(){
            Route::get('/pending-notices', 'pending_notices');
            Route::get('/all-notices', 'index');
            Route::post('/notices/acknowledge', 'acknowledge_notice');
        });
    });

    Route::prefix('property-manager')->group(function(){
        Route::controller(ManagerPropertyController::class)->group(function(){
            Route::get('/properties', 'index')->name('manager,property.index');
            Route::post('/properties', 'store')->name('manager.propery.store');
            Route::post('/properties/{uuid}/units', 'store_unit')->name('manager.propertyUnit.store');
            Route::post('/property-units/{uuid}/tenants', 'store_tenant')->name('manager.propertyTenant.store');
            Route::get('/property-tenants', 'tenants')->name('manager.tenants.index');
            Route::put('/property-tenants/{uuid}', 'update_tenant')->name('manager.propertyTenant.update');
            Route::post('/landlords', 'store_landlord')->name('manager.landlord.store');
        });

        Route::controller(ManagerReminderController::class)->group(function(){
            Route::post('/reminders', 'store')->name('manager.reminder.store');
            Route::post('/tenants/{uuid}/send-reminder', 'send_reminder');
            Route::get('/reminders', 'index')->name('manager.reminder.index');
            Route::get('/reminders/{uuid}', 'show')->name('manager.reminder.show');
            Route::put('/reminders/{uuid}', 'update')->name('manager.reminder.update');
            Route::delete('/reminders/{uuid}', 'destroy')->name("manager.reminder.delete");
        });

        Route::controller(NoticeController::class)->group(function(){
            Route::get('/notices', 'index');
            Route::post('/notices', 'send_notice');
        });
    });

    Route::controller(NotificationController::class)->group(function(){
        Route::get('/notification-count', 'notification_count');
        Route::get('/notifications', 'index');
        Route::get('/notifications/{notification}/open', 'open');
        Route::get('/notifications/mark-all-as-opened', 'mark_all_as_opened');
        Route::get('/activity-logs', 'activity_logs');
    });
});