<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class MakeMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && !$this->user()->is_suspended;
    }

    public function rules(): array
    {
        return [
            // Action Arena game parameters
            'score' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'score_delta' => ['nullable', 'integer'],
            'level' => ['nullable', 'integer', 'min:1', 'max:99'],
            'combos' => ['nullable', 'integer', 'min:0', 'max:999'],
            'finished' => ['nullable', 'boolean'],
            'event' => ['nullable', 'string', 'max:120'],

            // Generic move parameters
            'index' => ['nullable', 'integer', 'min:0', 'max:8'],
            'choice' => ['nullable', 'string', 'in:rock,paper,scissors'],
            'column' => ['nullable', 'integer', 'min:0', 'max:6'],
            'from_row' => ['nullable', 'integer', 'min:0', 'max:7'],
            'from_col' => ['nullable', 'integer', 'min:0', 'max:7'],
            'to_row' => ['nullable', 'integer', 'min:0', 'max:7'],
            'to_col' => ['nullable', 'integer', 'min:0', 'max:7'],
            'promotion' => ['nullable', 'string', 'in:Q,R,B,N,q,r,b,n'],
        ];
    }
}
