<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FriendshipController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/friend-requests', [FriendshipController::class, 'store']);
    Route::post('/friend-requests/{friendship}/accept', [FriendshipController::class, 'accept']);
    Route::post('/friend-requests/{friendship}/decline', [FriendshipController::class, 'decline']);
    Route::post('/friend-requests/{friendship}/block', [FriendshipController::class, 'block']);
    Route::get('/friends', [FriendshipController::class, 'index']);
});
