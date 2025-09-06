<?php

use Illuminate\Support\Facades\Route;
use App\AiChatV2\Http\Controllers\ChatController;

// AI Chat V2 Routes
Route::get('/', [ChatController::class, 'index'])->name('index');
Route::get('/session/{sessionId}', [ChatController::class, 'show'])->name('show');
Route::post('/session', [ChatController::class, 'create'])->name('create');

// Test route
Route::get('/test', function () {
    return view('ai-chat-v2.test');
})->name('test');

// Test bubble route
Route::get('/test-bubble', function () {
    return view('ai-chat-v2.test-bubble');
})->name('test-bubble');