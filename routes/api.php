<?php

use App\Http\Controllers\ClientThreadController;
use App\Http\Controllers\CoachThreadController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageReadController;
use App\Http\Controllers\ThreadController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/coach/threads', [CoachThreadController::class, 'index']);
    Route::post('/coach/threads', [CoachThreadController::class, 'store']);
    Route::delete('/coach/threads/{thread}', [CoachThreadController::class, 'destroy']);
    Route::patch('/coach/threads/{thread}/restore', [CoachThreadController::class, 'restore']);

    Route::get('/client/threads', [ClientThreadController::class, 'index']);

    Route::get('/threads/{thread}', [ThreadController::class, 'show']);
    Route::post('/threads/{thread}/messages', [MessageController::class, 'store']);
    Route::patch('/threads/{thread}/messages/{message}/read', [MessageReadController::class, 'update']);
});
