<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use App\Enums\FriendshipStatus;
use App\Services\FriendshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function __construct(
        protected FriendshipService $friendshipService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'addressee_id' => 'required|integer|exists:users,id',
        ]);

        $requesterId = auth()->id();

        if ((int) $data['addressee_id'] === $requesterId) {
            return response()->json(['message' => 'You cannot send a friend request to yourself.'], 422);
        }

        $existing = $this->friendshipService->existingRelationship($requesterId, (int) $data['addressee_id']);

        if ($existing) {
            $message = match ($existing->status->value) {
                'pending' => 'A pending friend request already exists between you and this user.',
                'accepted' => 'You are already friends with this user.',
                'declined' => 'A previous request was declined. A new request has been sent.',
                'blocked' => 'This relationship is blocked.',
                default => 'A relationship already exists between you and this user.',
            };

            if ($existing->status === FriendshipStatus::Declined) {
                $existing->update(['status' => FriendshipStatus::Pending]);
                $this->friendshipService->dispatchForBoth($existing);
                return response()->json($this->friendshipService->loadRelation($existing), 201);
            }

            if ($existing->status === FriendshipStatus::Blocked) {
                return response()->json(['message' => $message], 422);
            }

            return response()->json(['message' => $message], 422);
        }

        $friendship = Friendship::create([
            'requester_id' => $requesterId,
            'addressee_id' => $data['addressee_id'],
            'status' => FriendshipStatus::Pending,
        ]);

        $this->friendshipService->dispatchForBoth($friendship);

        return response()->json($this->friendshipService->loadRelation($friendship), 201);
    }

    public function accept(Request $request, Friendship $friendship): JsonResponse
    {
        $userId = auth()->id();

        if ($friendship->addressee_id !== $userId) {
            return response()->json(['message' => 'Only the addressee can accept this request.'], 403);
        }

        if ($friendship->status !== FriendshipStatus::Pending) {
            return response()->json(['message' => 'Only pending requests can be accepted.'], 422);
        }

        $friendship->update(['status' => FriendshipStatus::Accepted]);

        $this->friendshipService->dispatchForBoth($friendship);

        return response()->json($this->friendshipService->loadRelation($friendship));
    }

    public function decline(Request $request, Friendship $friendship): JsonResponse
    {
        $userId = auth()->id();

        if ($friendship->addressee_id !== $userId) {
            return response()->json(['message' => 'Only the addressee can decline this request.'], 403);
        }

        if ($friendship->status !== FriendshipStatus::Pending) {
            return response()->json(['message' => 'Only pending requests can be declined.'], 422);
        }

        $friendship->update(['status' => FriendshipStatus::Declined]);

        $this->friendshipService->dispatchForBoth($friendship);

        return response()->json($this->friendshipService->loadRelation($friendship));
    }

    public function block(Request $request, Friendship $friendship): JsonResponse
    {
        $userId = auth()->id();

        if ($friendship->requester_id !== $userId && $friendship->addressee_id !== $userId) {
            return response()->json(['message' => 'You are not a participant in this relationship.'], 403);
        }

        if ($friendship->status === FriendshipStatus::Blocked) {
            return response()->json(['message' => 'This relationship is already blocked.'], 422);
        }

        $friendship->update([
            'status' => FriendshipStatus::Blocked,
            'blocked_by' => $userId,
        ]);

        $this->friendshipService->dispatchForBoth($friendship);

        return response()->json($this->friendshipService->loadRelation($friendship));
    }

    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $friendIds = Friendship::where('status', FriendshipStatus::Accepted)
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)->orWhere('addressee_id', $userId);
            })
            ->get()
            ->map(function (Friendship $f) use ($userId) {
                return $f->requester_id === $userId ? $f->addressee_id : $f->requester_id;
            });

        if ($friendIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $friends = User::whereIn('id', $friendIds)
            ->get(['id', 'name', 'email', 'is_online', 'last_seen']);

        return response()->json(['data' => $friends]);
    }
}
