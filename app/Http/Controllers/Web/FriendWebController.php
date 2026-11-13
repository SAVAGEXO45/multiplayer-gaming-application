<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\User;
use App\Services\FriendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class FriendWebController extends Controller
{
    public function __construct(
        private readonly FriendService $friendService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));

        $friends = $user->friends()
            ->with('globalStatistic')
            ->orderByDesc('is_online')
            ->get();

        $friendIds = $friends->pluck('id')->all();

        $incomingRequests = $user->receivedFriendRequests()
            ->with('sender.globalStatistic')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $outgoingRequests = $user->sentFriendRequests()
            ->with('receiver.globalStatistic')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $pendingUserIds = array_merge(
            $incomingRequests->pluck('sender_id')->all(),
            $outgoingRequests->pluck('receiver_id')->all()
        );

        $searchResults = collect();
        if ($search !== '') {
            $searchResults = User::with('globalStatistic')
                ->where('id', '!=', $user->id)
                ->where('is_suspended', false)
                ->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })
                ->limit(16)
                ->get();
        } else {
            // Suggested players
            $searchResults = User::with('globalStatistic')
                ->where('id', '!=', $user->id)
                ->whereNotIn('id', $friendIds)
                ->where('is_suspended', false)
                ->orderByDesc('is_online')
                ->limit(8)
                ->get();
        }

        // Recent friend activity
        $friendRoomIds = \App\Models\RoomPlayer::whereIn('user_id', $friendIds)->pluck('game_room_id');
        $recentFriendActivity = GameSession::with(['game', 'room.roomPlayers.user', 'winner'])
            ->whereIn('game_room_id', $friendRoomIds)
            ->where('status', 'completed')
            ->latest('ended_at')
            ->limit(6)
            ->get();

        // Active rooms hosted by current user for quick invites
        $myOpenRooms = GameRoom::with('game')
            ->where('host_user_id', $user->id)
            ->where('status', 'waiting')
            ->get();

        return view('friends.index', compact(
            'friends',
            'friendIds',
            'pendingUserIds',
            'incomingRequests',
            'outgoingRequests',
            'search',
            'searchResults',
            'recentFriendActivity',
            'myOpenRooms'
        ));
    }

    public function sendRequest(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ]);

        try {
            $receiver = User::where('username', strtolower($request->input('username')))->firstOrFail();
            $req = $this->friendService->sendRequest($request->user(), $receiver);

            $msg = $req->status === 'accepted'
                ? "You and {$receiver->username} are now friends!"
                : "Friend request sent to {$receiver->username}!";

            return back()->with('success', $msg);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }
    }

    public function acceptRequest(Request $request, FriendRequest $friendRequest): RedirectResponse
    {
        try {
            $this->friendService->acceptRequest($friendRequest, $request->user());

            return back()->with('success', 'Friend request accepted!');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }
    }

    public function rejectRequest(Request $request, FriendRequest $friendRequest): RedirectResponse
    {
        try {
            $this->friendService->rejectRequest($friendRequest, $request->user());

            return back()->with('success', 'Friend request declined.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }
    }

    public function removeFriend(Request $request, User $friend): RedirectResponse
    {
        $this->friendService->removeFriend($request->user(), $friend);

        return back()->with('success', "Removed {$friend->username} from your friends list.");
    }
}
