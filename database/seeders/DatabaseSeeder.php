<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * This seeder creates test data for the chat application:
     * - 5 test users with realistic names and emails
     * - 3 conversations between different user pairs
     * - 15 messages distributed across conversations
     * - Mix of read and unread messages for testing
     *
     * @return void
     */
    public function run(): void
    {
        // Create 5 test users
        $users = User::factory()->count(5)->create();
        
        // Create 3 conversations between different user pairs
        // Conversation 1: User 1 and User 2
        Conversation::create([
            'user_one_id' => min($users[0]->id, $users[1]->id),
            'user_two_id' => max($users[0]->id, $users[1]->id),
        ]);
        
        // Conversation 2: User 1 and User 3
        Conversation::create([
            'user_one_id' => min($users[0]->id, $users[2]->id),
            'user_two_id' => max($users[0]->id, $users[2]->id),
        ]);
        
        // Conversation 3: User 2 and User 4
        Conversation::create([
            'user_one_id' => min($users[1]->id, $users[3]->id),
            'user_two_id' => max($users[1]->id, $users[3]->id),
        ]);
        
        // Create 15 messages across the conversations
        Message::factory()->count(15)->create();
    }
}