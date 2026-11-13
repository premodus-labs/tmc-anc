<?php

namespace App\Http\Requests\V1;

use App\Models\Visitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('visitation'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([Visitation::STATUS_COMPLETED, Visitation::STATUS_CANCELLED])],
            // A cancelled visit must say why (left before being seen, registered by mistake, ...)
            'closure_reason' => ['nullable', 'required_if:status,'.Visitation::STATUS_CANCELLED, 'string', 'max:500'],
        ];
    }
}
