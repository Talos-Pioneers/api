<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlueprintCollectionController;
use App\Http\Controllers\BlueprintController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('register', [RegisterController::class, 'store']);
Route::post('login', [LoginController::class, 'store']);

Route::apiResource('tags', TagController::class)->only(['index']);
Route::apiResource('blueprints', BlueprintController::class)->only(['index', 'show']);
Route::apiResource('collections', BlueprintCollectionController::class)->only(['index', 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::apiResource('tags', TagController::class)->except(['index']);
    Route::apiResource('blueprints', BlueprintController::class)->except(['index', 'show']);
    Route::post('blueprints/{blueprint}/like', [BlueprintController::class, 'like']);
    Route::post('blueprints/{blueprint}/copy', [BlueprintController::class, 'copy']);
    Route::apiResource('collections', BlueprintCollectionController::class)->except(['index', 'show']);
    Route::post('users/{user}/upgrade-to-moderator', [UserController::class, 'upgradeToModerator']);
});
