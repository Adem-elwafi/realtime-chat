<?php

namespace App\Console\Commands;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:broadcast {conversation_id : The ID of the conversation to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test broadcast message to a specific conversation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $conversationId = $this->argument('conversation_id');
        
        $this->info('🔍 Testing broadcast for conversation ID: ' . $conversationId);
        
        // Find the conversation
        $conversation = Conversation::find($conversationId);
        
        if (!$conversation) {
            $this->error('❌ Conversation not found!');
            return 1;
        }
        
        $this->info('✅ Conversation found');
        $this->line('   User One ID: ' . $conversation->user_one_id);
        $this->line('   User Two ID: ' . $conversation->user_two_id);
        
        // Create a test message
        $this->info('📝 Creating test message...');
        
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $conversation->user_one_id,
            'message' => '🧪 TEST BROADCAST from CLI - ' . now()->format('Y-m-d H:i:s'),
            'is_read' => false,
        ]);
        
        $this->info('✅ Test message created (ID: ' . $message->id . ')');
        
        // Log broadcast attempt
        Log::info('🧪 CLI Test: About to broadcast test message', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'channel' => 'chat.' . $conversation->id,
        ]);
        
        // Broadcast the event
        $this->info('📡 Broadcasting MessageSent event...');
        $this->line('   Channel: chat.' . $conversation->id);
        
        try {
            broadcast(new MessageSent($message));
            $this->info('✅ Broadcast completed successfully!');
            
            $this->newLine();
            $this->info('🎯 Next steps:');
            $this->line('1. Check Laravel logs: tail -f storage/logs/laravel.log');
            $this->line('2. Check Reverb terminal output');
            $this->line('3. Check browser console if you have the chat open');
            $this->line('4. The message should appear in real-time without refresh');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Broadcast failed!');
            $this->error('   Error: ' . $e->getMessage());
            
            Log::error('🧪 CLI Test: Broadcast failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
}
