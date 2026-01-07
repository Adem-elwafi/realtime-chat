<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The messages table stores individual chat messages within conversations.
     * Each message belongs to one conversation and has one sender.
     * Read receipts are tracked with is_read and read_at fields.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            // Primary key - auto-incrementing ID
            $table->id();
            
            // conversation_id: Foreign key linking to the parent conversation
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            
            // sender_id: Foreign key to the user who sent this message
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            
            // message: The actual text content of the message
            // Using 'text' type to accommodate longer messages
            $table->text('message');
            
            // is_read: Boolean flag indicating if the recipient has read this message
            $table->boolean('is_read')->default(false);
            
            // read_at: Timestamp when the message was actually read by the recipient
            // This allows for more detailed analytics about reading behavior
            $table->timestamp('read_at')->nullable();
            
            // Laravel's automatic timestamps (created_at, updated_at)
            $table->timestamps();
            
            // Add indexes for performance on frequently queried columns
            $table->index('conversation_id');
            $table->index('sender_id');
            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop the messages table if it exists.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};