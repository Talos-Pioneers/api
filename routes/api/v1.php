<?php

use App\Http\Controllers\V1\Admin\UserController;
use App\Http\Controllers\V1\Auth\ProfileController;
use App\Http\Controllers\V1\Blueprint\BlueprintCollectionController;
use App\Http\Controllers\V1\Blueprint\BlueprintController;
use App\Http\Controllers\V1\Blueprint\TagController;
use Illuminate\Support\Facades\Route;

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
