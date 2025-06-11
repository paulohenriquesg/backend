<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'view'])->name('login.view');
Route::get('/login/success', function () {
    return view('login-success');
})->name('login.success');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::get('register', [RegisterController::class, 'view'])->name('register.view');
Route::post('register', [RegisterController::class, 'register'])->name('register.submit');
