<?php

use App\Http\Controllers\V1\Admin\UserController;
use App\Http\Controllers\V1\Auth\ProfileController;
use App\Http\Controllers\V1\Blueprint\BlueprintCollectionController;
use App\Http\Controllers\V1\Blueprint\BlueprintCommentController;
use App\Http\Controllers\V1\Blueprint\BlueprintController;
use App\Http\Controllers\V1\Blueprint\MyBlueprintsController;
use App\Http\Controllers\V1\Blueprint\MyCollectionsController;
use App\Http\Controllers\V1\Blueprint\TagController;
use App\Http\Controllers\V1\FacilityController;
use App\Http\Controllers\V1\ItemController;
use Illuminate\Support\Facades\Route;

Route::apiResource('tags', TagController::class)->only(['index']);
Route::apiResource('blueprints', BlueprintController::class)->only(['index', 'show']);
Route::apiResource('collections', BlueprintCollectionController::class)->only(['index', 'show']);
Route::apiResource('facilities', FacilityController::class)->only(['index', 'show']);
Route::apiResource('items', ItemController::class)->only(['index', 'show']);

// Comments routes (public read access)
Route::get('blueprints/{blueprint}/comments', [BlueprintCommentController::class, 'index']);
Route::get('blueprints/{blueprint}/comments/{comment}', [BlueprintCommentController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::apiResource('tags', TagController::class)->except(['index']);
    Route::apiResource('blueprints', BlueprintController::class)->except(['index', 'show']);
    Route::post('blueprints/{blueprint}/like', [BlueprintController::class, 'like']);
    Route::post('blueprints/{blueprint}/copy', [BlueprintController::class, 'copy']);
    Route::apiResource('collections', BlueprintCollectionController::class)->except(['index', 'show']);
    Route::post('users/{user}/upgrade-to-moderator', [UserController::class, 'upgradeToModerator']);

    // My blueprints and collections routes
    Route::get('my/blueprints', [MyBlueprintsController::class, 'index']);
    Route::get('my/collections', [MyCollectionsController::class, 'index']);

    // Comments routes (authenticated write access)
    Route::post('blueprints/{blueprint}/comments', [BlueprintCommentController::class, 'store']);
    Route::put('blueprints/{blueprint}/comments/{comment}', [BlueprintCommentController::class, 'update']);
    Route::delete('blueprints/{blueprint}/comments/{comment}', [BlueprintCommentController::class, 'destroy']);
});
