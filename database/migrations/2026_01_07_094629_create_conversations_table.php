<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The conversations table stores 1-on-1 chat conversations between two users.
     * Each conversation has exactly two participants (user_one_id and user_two_id).
     * The last_message_at field helps sort conversations by recency.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            // Primary key - auto-incrementing ID
            $table->id();
            
            // user_one_id: Foreign key to the first participant in the conversation
            $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
            
            // user_two_id: Foreign key to the second participant in the conversation
            $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade');
            
            // last_message_at: Timestamp of the most recent message in this conversation
            // Used for sorting conversations by activity (most recent first)
            $table->timestamp('last_message_at')->nullable();
            
            // Laravel's automatic timestamps (created_at, updated_at)
            $table->timestamps();
            
            // Add indexes for performance on frequently queried columns
            $table->index('user_one_id');
            $table->index('user_two_id');
            
            // Ensure unique conversations between the same two users
            // This prevents duplicate conversations between the same user pair
            // Note: We don't care about the order (user_one/user_two) for uniqueness
            $table->unique(['user_one_id', 'user_two_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop the conversations table if it exists.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};