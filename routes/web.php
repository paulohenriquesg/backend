<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    $deviceName = request('device_name');
    $redirect = request('redirect');

    return view('login', compact('deviceName', 'redirect'));
});

Route::get('/login/success', function () {
    return view('login-success');
})->name('login.success');

Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
