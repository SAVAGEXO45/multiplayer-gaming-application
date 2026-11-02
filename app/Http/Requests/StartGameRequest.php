<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartGameRequest extends FormRequest
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
            'difficulty' => ['required', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'points' => ['prohibited'],
            'score' => ['prohibited'],
            'winner_id' => ['prohibited'],
        ];
    }
}
