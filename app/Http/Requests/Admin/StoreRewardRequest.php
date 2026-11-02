<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRewardRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('rewards', 'slug')],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['title', 'badge', 'theme'])],
            'icon' => ['required', 'string', 'max:64'],
            'cost' => ['required', 'integer', 'between:1,1000000'],
            'stock' => ['nullable', 'integer', 'between:0,1000000'],
            'payload' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
