<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRewardRequest;
use App\Http\Requests\Admin\UpdateRewardRequest;
use App\Models\Reward;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RewardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Rewards', [
            'rewards' => Reward::query()
                ->withCount('transactions')
                ->orderBy('cost')
                ->get()
                ->map(fn (Reward $reward): array => [
                    'id' => $reward->id,
                    'slug' => $reward->slug,
                    'name' => $reward->name,
                    'description' => $reward->description,
                    'type' => $reward->type,
                    'icon' => $reward->icon,
                    'cost' => $reward->cost,
                    'stock' => $reward->stock,
                    'is_active' => $reward->is_active,
                    'claims' => $reward->transactions_count,
                ]),
        ]);
    }

    public function store(StoreRewardRequest $request): RedirectResponse
    {
        Reward::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reward created.']);

        return back();
    }

    public function update(UpdateRewardRequest $request, Reward $reward): RedirectResponse
    {
        $reward->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reward updated.']);

        return back();
    }
}
