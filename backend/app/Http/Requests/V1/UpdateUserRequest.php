<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        // Activating or deactivating needs the stricter check: not on yourself
        return $this->has('is_active')
            ? $this->user()->can('manageAccess', $target)
            : $this->user()->can('update', $target);
    }

    // Lowercase so "MBanda" and "mbanda" cannot both exist
    protected function prepareForValidation(): void
    {
        if (is_string($this->username)) {
            $this->merge(['username' => mb_strtolower($this->username)]);
        }
    }

    /**
     * Partial updates: only the fields sent are validated and changed.
     */
    public function rules(): array
    {
        return [
            'username' => [
                'sometimes', 'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                Rule::unique('app_user', 'username')->ignore($this->route('user')),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
