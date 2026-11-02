<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
            'points_win' => ['sometimes', 'integer', 'between:0,10000'],
            'points_draw' => ['sometimes', 'integer', 'between:0,10000'],
            'points_loss' => ['sometimes', 'integer', 'between:-10000,0'],
            'points_participation' => ['sometimes', 'integer', 'between:0,10000'],
            'max_players' => ['sometimes', 'integer', 'between:2,8'],
        ];
    }
}
