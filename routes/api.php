<?php

use Illuminate\Support\Facades\Route;

Route::get('user', function () {
    return response()->json([
        'user' => request()->user(),
    ]);
})->middleware('auth:sanctum');

Route::prefix('v1')->group(base_path('routes/api/v1.php'));
