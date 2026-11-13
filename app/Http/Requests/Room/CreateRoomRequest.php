<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && !$this->user()->is_suspended;
    }

    public function rules(): array
    {
        return [
            'game_slug' => ['required', 'string', 'exists:games,slug'],
            'name' => ['nullable', 'string', 'max:90'],
            'visibility' => ['required', 'string', 'in:public,private'],
        ];
    }
}
