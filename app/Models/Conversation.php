<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * These fields can be filled using mass assignment (create/update methods).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * This ensures proper data type handling for date fields.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the first participant in the conversation.
     *
     * This relationship links to the User model for user_one_id.
     *
     * @return BelongsTo
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * Get the second participant in the conversation.
     *
     * This relationship links to the User model for user_two_id.
     *
     * @return BelongsTo
     */
    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Get all messages in this conversation.
     *
     * This relationship returns all Message models associated with this conversation,
     * ordered by creation time (oldest first).
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the other user in the conversation.
     *
     * Given the current user's ID, this method returns the other participant.
     * Useful for determining who the conversation partner is.
     *
     * @param int $currentUserId The ID of the current user
     * @return User|null The other user in the conversation, or null if not found
     */
    public function getOtherUser(int $currentUserId): ?User
    {
        if ($this->user_one_id === $currentUserId) {
            return $this->userTwo;
        }
        
        if ($this->user_two_id === $currentUserId) {
            return $this->userOne;
        }
        
        return null; // Current user is not part of this conversation
    }

    /**
     * Scope to find a conversation between two specific users.
     *
     * This query scope handles both possible orderings of users in the conversation
     * (user_one_id = A, user_two_id = B) OR (user_one_id = B, user_two_id = A).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userOneId ID of the first user
     * @param int $userTwoId ID of the second user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenUsers($query, int $userOneId, int $userTwoId)
    {
        return $query->where(function ($q) use ($userOneId, $userTwoId) {
            $q->where('user_one_id', $userOneId)
              ->where('user_two_id', $userTwoId);
        })->orWhere(function ($q) use ($userOneId, $userTwoId) {
            $q->where('user_one_id', $userTwoId)
              ->where('user_two_id', $userOneId);
        });
    }
}