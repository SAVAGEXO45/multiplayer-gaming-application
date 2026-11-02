<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'position' => ['nullable', 'integer', 'between:0,8'],
            'choice' => ['nullable', 'string', Rule::in(['rock', 'paper', 'scissors'])],
            'guess' => ['nullable', 'integer', 'between:1,100'],
            'points' => ['prohibited'],
            'points_awarded' => ['prohibited'],
            'score' => ['prohibited'],
            'result' => ['prohibited'],
            'winner_id' => ['prohibited'],
            'is_draw' => ['prohibited'],
        ];
    }
}
