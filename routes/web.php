<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatTypingController;

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

Route::post('/chat/typing', [ChatController::class, 'sendTypingIndicator']);
Route::post('/chat/typing', [ChatTypingController::class, 'store'])
    ->middleware('auth');

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
    
    // Debug routes - for testing broadcast functionality
    Route::get('/debug/broadcast', function () {
        $conversations = auth()->user()->conversations;
        return view('debug.broadcast-test', compact('conversations'));
    })->name('debug.broadcast');
    
    Route::post('/debug/test-broadcast/{conversationId}', function ($conversationId) {
        $conversation = \App\Models\Conversation::findOrFail($conversationId);
        
        // Verify user has access
        if ($conversation->user_one_id != auth()->id() && $conversation->user_two_id != auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        // Create a test message
        $message = \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'message' => '🧪 TEST MESSAGE - ' . now()->format('H:i:s'),
            'is_read' => false,
        ]);
        
        // Broadcast it
        \Illuminate\Support\Facades\Log::info('🧪 Debug: Broadcasting test message', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
        ]);
        
        broadcast(new \App\Events\MessageSent($message));
        
        return response()->json([
            'success' => true,
            'message' => 'Test broadcast sent successfully',
            'message_id' => $message->id,
            'channel' => 'chat.' . $conversation->id,
        ]);
    })->name('debug.test-broadcast');
});

// Include authentication routes from Breeze
require __DIR__.'/auth.php';