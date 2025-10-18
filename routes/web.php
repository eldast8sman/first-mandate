<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/bvn-redirect', function(){
    return view('bvn-redirect');
});

Route::get('/payments/verify', function(){
    return view('payments-verify');
});

// Cron job route for reminder emails
Route::get('/cron/send-reminder-emails', [App\Http\Controllers\CronController::class, 'sendReminderEmails']);
