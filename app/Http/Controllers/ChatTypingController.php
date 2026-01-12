<?php

// app/Http/Controllers/ChatTypingController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\UserTyping;

class ChatTypingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
        ]);

        broadcast(
            new UserTyping(
                $request->conversation_id,
                $request->user()
            )
        )->toOthers();

        return response()->json(['ok' => true]);
    }
}
