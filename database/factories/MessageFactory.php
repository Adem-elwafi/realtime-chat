<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        // Get a random conversation
        $conversation = Conversation::inRandomOrder()->first();
        
        if (!$conversation) {
            $conversation = Conversation::factory()->create();
        }
        
        // Randomly pick one of the two users in the conversation as sender
        $senderId = $this->faker->boolean ? $conversation->user_one_id : $conversation->user_two_id;
        
        return [
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'message' => $this->faker->sentence(),
            'is_read' => $this->faker->boolean(70), // 70% chance of being read
            'read_at' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
        ];
    }
}