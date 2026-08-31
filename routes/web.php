<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Root redirect to conversations
    Route::get('/', [ConversationController::class, 'index'])->name('chat.index');

    // Conversation & Message Web Routes
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    
    // Message store endpoint with rate limiting to prevent spam
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])
        ->middleware('throttle:messages')
        ->name('messages.store');

    Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markAsRead'])->name('conversations.read');
});
