<?php

use App\Http\Controllers\V1\Auth\ProviderController;
use Illuminate\Support\Facades\Route;

Route::get('auth/{provider}/redirect', [ProviderController::class, 'redirect'])->name('auth.provider.redirect');
Route::get('auth/{provider}/callback', [ProviderController::class, 'callback'])->name('auth.provider.callback');
