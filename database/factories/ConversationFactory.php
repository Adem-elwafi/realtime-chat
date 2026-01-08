<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        // Get two random users (ensure they're different)
        $users = User::inRandomOrder()->take(2)->get();
        
        if ($users->count() < 2) {
            // If we don't have enough users, create some
            User::factory()->create();
            User::factory()->create();
            $users = User::inRandomOrder()->take(2)->get();
        }
        
        $userOneId = $users[0]->id;
        $userTwoId = $users[1]->id;
        
        // Ensure consistent ordering (smaller ID first)
        if ($userOneId > $userTwoId) {
            [$userOneId, $userTwoId] = [$userTwoId, $userOneId];
        }

        return [
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
            'last_message_at' => null,
        ];
    }
}