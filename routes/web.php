<?php

use App\Http\Controllers\V1\Auth\LoginController;
use App\Http\Controllers\V1\Auth\ProviderController;
use App\Http\Controllers\V1\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::post('login', [LoginController::class, 'store'])->name('login');
Route::post('register', [RegisterController::class, 'store']);

Route::get('auth/{provider}/redirect', [ProviderController::class, 'redirect'])->name('auth.provider.redirect');
Route::get('auth/{provider}/callback', [ProviderController::class, 'callback'])->name('auth.provider.callback');
Route::get('/', function () {
    return view('welcome');
});
