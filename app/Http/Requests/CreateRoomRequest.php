<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoomRequest extends FormRequest
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
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'name' => ['required', 'string', 'min:3', 'max:60'],
            'max_players' => ['required', 'integer', 'between:2,8'],
            'is_private' => ['boolean'],
            'password' => ['nullable', 'string', 'min:4', 'max:50', 'required_if:is_private,true'],
        ];
    }
}
