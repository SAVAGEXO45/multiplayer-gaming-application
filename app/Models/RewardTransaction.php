<?php

namespace App\Models;

use App\Enums\RewardTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_id',
        'type',
        'amount',
        'balance_after',
        'reason',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => RewardTransactionType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}
