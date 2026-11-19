<?php

namespace App\Http\Requests\V1;

use App\Models\Visitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('visitation'));
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', Rule::in(Visitation::PRIORITIES)],
        ];
    }
}
