<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class SecurityEventService
{
    public function log(
        string $type,
        string $description,
        ?User $user = null,
        string $severity = 'low',
        array $metadata = [],
        ?Request $request = null,
    ): SecurityEvent {
        try {
            return SecurityEvent::create([
                'user_id' => $user?->id,
                'type' => $type,
                'severity' => $severity,
                'description' => $description,
                'ip_address' => $request?->ip(),
                'metadata' => $metadata ?: null,
            ]);
        } catch (Throwable) {
            return new SecurityEvent([
                'type' => $type,
                'severity' => $severity,
                'description' => $description,
            ]);
        }
    }
}
