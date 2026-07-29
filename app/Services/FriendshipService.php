<?php

namespace App\Services;

use App\Models\Friendship;
use App\Events\FriendshipUpdated;
use App\Enums\FriendshipStatus;

class FriendshipService
{
    public function existingRelationship(int $userIdA, int $userIdB): ?Friendship
    {
        return Friendship::where(function ($q) use ($userIdA, $userIdB) {
            $q->where('requester_id', $userIdA)->where('addressee_id', $userIdB);
        })->orWhere(function ($q) use ($userIdA, $userIdB) {
            $q->where('requester_id', $userIdB)->where('addressee_id', $userIdA);
        })->first();
    }

    public function dispatchForBoth(Friendship $friendship): void
    {
        broadcast(new FriendshipUpdated($friendship, $friendship->requester_id));
        broadcast(new FriendshipUpdated($friendship, $friendship->addressee_id));
    }

    public function loadRelation(Friendship $friendship): Friendship
    {
        return $friendship->load(['requester:id,name,email,is_online,last_seen', 'addressee:id,name,email,is_online,last_seen']);
    }
}
