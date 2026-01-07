<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_online',
        'last_seen',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'last_seen' => 'datetime',
        ];
    }

    /**
     * Get all conversations where this user is the first participant.
     *
     * @return HasMany
     */
    public function conversationsAsUserOne(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    /**
     * Get all conversations where this user is the second participant.
     *
     * @return HasMany
     */
    public function conversationsAsUserTwo(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }

    /**
     * Get all conversations for this user (both as user_one and user_two).
     *
     * This accessor combines conversations where the user appears in either position,
     * ordered by most recent activity.
     *
     * @return Collection<int, Conversation>
     */
    public function getConversationsAttribute(): Collection
    {
        return Conversation::where('user_one_id', $this->id)
            ->orWhere('user_two_id', $this->id)
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    /**
     * Get all messages sent by this user.
     *
     * @return HasMany
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Find or create a conversation with another user.
     *
     * This method ensures that there's only one conversation between two users.
     * If a conversation already exists, it returns the existing one.
     * If no conversation exists, it creates a new one with the current user and the specified user.
     *
     * @param int $otherUserId The ID of the other user to start a conversation with
     * @return Conversation The conversation model instance
     * @throws \InvalidArgumentException If trying to create a conversation with oneself
     */
    public function getConversationWith(int $otherUserId): Conversation
    {
        // Prevent creating conversations with oneself
        if ($this->id === $otherUserId) {
            throw new \InvalidArgumentException('Cannot create conversation with yourself');
        }
        
        // Try to find existing conversation (handles both user orderings)
        $conversation = Conversation::betweenUsers($this->id, $otherUserId)->first();
        
        if ($conversation) {
            return $conversation;
        }
        
        // Create new conversation with consistent ordering (smaller ID as user_one)
        $userOneId = min($this->id, $otherUserId);
        $userTwoId = max($this->id, $otherUserId);
        
        return Conversation::create([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }
}