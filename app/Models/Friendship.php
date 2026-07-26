<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\FriendshipStatus;

class Friendship extends Model
{
    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
        'blocked_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => FriendshipStatus::class,
        ];
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee()
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    public function blockedBy()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
