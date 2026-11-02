<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameResult;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $severity = $request->query('severity');
        $type = trim((string) $request->query('type'));

        $events = SecurityEvent::query()
            ->with('user')
            ->when(in_array($severity, ['low', 'medium', 'high'], true), fn ($query) => $query->where('severity', $severity))
            ->when($type !== '', fn ($query) => $query->where('type', 'like', "%{$type}%"))
            ->latest()
            ->paginate(20, ['*'], 'events_page')
            ->withQueryString()
            ->through(fn (SecurityEvent $event): array => [
                'id' => $event->id,
                'type' => $event->type,
                'severity' => $event->severity,
                'description' => $event->description,
                'ip_address' => $event->ip_address,
                'user' => $event->user?->username,
                'metadata' => $event->metadata,
                'created_at' => $event->created_at?->toIso8601String(),
            ]);

        $results = GameResult::query()
            ->with(['user', 'game'])
            ->latest()
            ->paginate(20, ['*'], 'results_page')
            ->withQueryString()
            ->through(fn (GameResult $result): array => [
                'id' => $result->id,
                'user' => $result->user->username,
                'game' => $result->game->name,
                'result' => $result->result->value,
                'points_awarded' => $result->points_awarded,
                'is_multiplayer' => $result->is_multiplayer,
                'created_at' => $result->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Activity', [
            'events' => $events,
            'results' => $results,
            'filters' => ['severity' => $severity, 'type' => $type],
        ]);
    }
}
