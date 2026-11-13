<?php

namespace App\Services;

use App\Models\FriendRequest;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FriendService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly AchievementService $achievementService
    ) {}

    public function sendRequest(User $sender, User $receiver): FriendRequest
    {
        if ($sender->id === $receiver->id) {
            throw new InvalidArgumentException('You cannot send a friend request to yourself.');
        }

        if ($this->areFriends($sender->id, $receiver->id)) {
            throw new InvalidArgumentException("You are already friends with {$receiver->username}.");
        }

        // Check if receiver already sent a pending request to sender
        $reversePending = FriendRequest::where('sender_id', $receiver->id)
            ->where('receiver_id', $sender->id)
            ->where('status', 'pending')
            ->first();

        if ($reversePending) {
            return $this->acceptRequest($reversePending, $sender);
        }

        $existing = FriendRequest::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->first();

        if ($existing && $existing->status === 'pending') {
            throw new InvalidArgumentException('A friend request is already pending for this player.');
        }

        $request = FriendRequest::updateOrCreate(
            [
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
            ],
            [
                'status' => 'pending',
                'responded_at' => null,
            ]
        );

        $this->notificationService->notify(
            $receiver,
            'friend_request',
            'New Friend Request',
            "{$sender->username} sent you a friend request.",
            route('friends.index'),
            ['sender_id' => $sender->id, 'sender_username' => $sender->username]
        );

        return $request;
    }

    public function acceptRequest(FriendRequest $friendRequest, User $actor): FriendRequest
    {
        if ((int) $friendRequest->receiver_id !== (int) $actor->id) {
            throw new InvalidArgumentException('You are not authorized to accept this friend request.');
        }

        if ($friendRequest->status !== 'pending') {
            throw new InvalidArgumentException('This friend request has already been processed.');
        }

        return DB::transaction(function () use ($friendRequest) {
            $friendRequest->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            Friendship::firstOrCreate([
                'user_id' => $friendRequest->sender_id,
                'friend_id' => $friendRequest->receiver_id,
            ]);

            Friendship::firstOrCreate([
                'user_id' => $friendRequest->receiver_id,
                'friend_id' => $friendRequest->sender_id,
            ]);

            $sender = User::find($friendRequest->sender_id);
            $receiver = User::find($friendRequest->receiver_id);

            if ($sender && $receiver) {
                $this->notificationService->notify(
                    $sender,
                    'friend_accepted',
                    'Friend Request Accepted',
                    "{$receiver->username} accepted your friend request!",
                    route('friends.index'),
                    ['friend_id' => $receiver->id, 'friend_username' => $receiver->username]
                );

                $this->achievementService->checkAndUnlock($sender);
                $this->achievementService->checkAndUnlock($receiver);
            }

            return $friendRequest->fresh(['sender', 'receiver']);
        });
    }

    public function rejectRequest(FriendRequest $friendRequest, User $actor): FriendRequest
    {
        if ((int) $friendRequest->receiver_id !== (int) $actor->id && (int) $friendRequest->sender_id !== (int) $actor->id) {
            throw new InvalidArgumentException('You are not authorized to modify this friend request.');
        }

        $friendRequest->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        return $friendRequest->fresh();
    }

    public function removeFriend(User $user, User $friend): void
    {
        DB::transaction(function () use ($user, $friend) {
            Friendship::where(function ($q) use ($user, $friend) {
                $q->where('user_id', $user->id)->where('friend_id', $friend->id);
            })->orWhere(function ($q) use ($user, $friend) {
                $q->where('user_id', $friend->id)->where('friend_id', $user->id);
            })->delete();

            FriendRequest::where(function ($q) use ($user, $friend) {
                $q->where('sender_id', $user->id)->where('receiver_id', $friend->id);
            })->orWhere(function ($q) use ($user, $friend) {
                $q->where('sender_id', $friend->id)->where('receiver_id', $user->id);
            })->delete();
        });
    }

    public function areFriends(int $userId, int $friendId): bool
    {
        return Friendship::where('user_id', $userId)
            ->where('friend_id', $friendId)
            ->exists();
    }
}
