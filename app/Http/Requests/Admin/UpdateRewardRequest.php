<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRewardRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:80'],
            'description' => ['sometimes', 'string', 'max:255'],
            'cost' => ['sometimes', 'integer', 'between:1,1000000'],
            'stock' => ['sometimes', 'nullable', 'integer', 'between:0,1000000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
