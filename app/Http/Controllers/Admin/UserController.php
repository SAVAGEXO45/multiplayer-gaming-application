<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Services\SecurityEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));
        $role = $request->query('role');
        $status = $request->query('status');

        $users = User::query()
            ->with('statistics')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($role, ['player', 'admin'], true), fn ($query) => $query->where('role', $role))
            ->when($status === 'suspended', fn ($query) => $query->where('is_suspended', true))
            ->when($status === 'active', fn ($query) => $query->where('is_suspended', false))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar_url' => $user->avatarUrl(),
                'role' => $user->role->value,
                'is_suspended' => $user->is_suspended,
                'suspension_reason' => $user->suspension_reason,
                'points' => (int) ($user->statistics?->points ?? 0),
                'games_played' => (int) ($user->statistics?->games_played ?? 0),
                'wins' => (int) ($user->statistics?->wins ?? 0),
                'win_rate' => $user->statistics?->winRate() ?? 0,
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Users', [
            'users' => $users,
            'filters' => ['search' => $search, 'role' => $role, 'status' => $status],
        ]);
    }

    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user,
        SecurityEventService $security,
    ): RedirectResponse {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['user' => 'You cannot change your own account status.']);
        }

        DB::transaction(function () use ($request, $user, $security): void {
            if ($request->validated('action') === 'suspend') {
                $user->update([
                    'is_suspended' => true,
                    'suspended_at' => now(),
                    'suspension_reason' => $request->validated('reason'),
                ]);

                $user->tokens()->delete();

                $security->log(
                    'admin.suspend_user',
                    "Administrator {$request->user()->username} suspended {$user->username}.",
                    $user,
                    'high',
                    ['reason' => $request->validated('reason')],
                    $request,
                );
            } else {
                $user->update([
                    'is_suspended' => false,
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ]);

                $security->log(
                    'admin.reactivate_user',
                    "Administrator {$request->user()->username} reactivated {$user->username}.",
                    $user,
                    'medium',
                    [],
                    $request,
                );
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account status updated.']);

        return back();
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user, SecurityEventService $security): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['user' => 'You cannot change your own role.']);
        }

        $previous = $user->role->value;
        $user->update(['role' => $request->validated('role')]);

        $security->log(
            'admin.role_change',
            "Administrator {$request->user()->username} changed {$user->username} from {$previous} to {$user->role->value}.",
            $user,
            'high',
            [],
            $request,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User role updated.']);

        return back();
    }
}
