<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\GameRoom;
use App\Services\GameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $rooms = GameRoom::query()
            ->with(['game', 'host', 'activePlayers'])
            ->when(in_array($status, ['waiting', 'in_progress', 'finished', 'closed'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (GameRoom $room): array => [
                'code' => $room->code,
                'name' => $room->name,
                'status' => $room->status->value,
                'is_private' => $room->is_private,
                'game' => $room->game->name,
                'host' => $room->host->username,
                'players_count' => $room->activePlayers->count(),
                'max_players' => $room->max_players,
                'created_at' => $room->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Rooms', [
            'rooms' => $rooms,
            'status' => $status,
        ]);
    }

    public function close(GameRoom $room, GameService $games): RedirectResponse
    {
        if ($room->currentSession !== null && $room->currentSession->isActive()) {
            $games->abandon($room->currentSession);
        }

        $room->update([
            'status' => RoomStatus::Closed,
            'finished_at' => now(),
        ]);

        $room->players()->update(['is_ready' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Room closed.']);

        return back();
    }
}
