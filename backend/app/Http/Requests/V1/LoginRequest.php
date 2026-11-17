<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    // Usernames are stored lowercase, so "Nurse1" logs in as "nurse1"
    protected function prepareForValidation(): void
    {
        if (is_string($this->username)) {
            $this->merge(['username' => mb_strtolower($this->username)]);
        }
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
