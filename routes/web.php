<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home route - redirects based on authentication status
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('chat.index');
    }
    return redirect()->route('login');
})->name('home');

// Dashboard route (redirect to chat)
Route::get('/dashboard', function () {
    return redirect()->route('chat.index');
})->middleware(['auth'])->name('dashboard');

// Chat routes - all protected by auth middleware
Route::middleware(['auth'])->group(function () {
    // Show list of conversations
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    
    // Show chat with specific user
    Route::get('/chat/{userId}', [ChatController::class, 'show'])->name('chat.show');
    
    // Send new message
    Route::post('/chat/message', [ChatController::class, 'store'])->name('chat.store');
    
    // Mark conversation as read
    Route::post('/chat/{conversationId}/read', [ChatController::class, 'markAsRead'])->name('chat.markAsRead');
    
    // User discovery routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
});

// Include authentication routes from Breeze
require __DIR__.'/auth.php';