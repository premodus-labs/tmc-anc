<?php

namespace App\Http\Requests\V1;

use App\Models\AppUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AppUser::class);
    }

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'integer', Rule::exists('staff', 'id'), Rule::unique('app_user', 'staff_id')],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique('app_user', 'username')],
            'password' => ['required', 'string', Password::defaults()],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists('role', 'code')],
        ];
    }

    public function messages(): array
    {
        return ['staff_id.unique' => 'This staff member already has a login.'];
    }
}
