<?php

namespace App\Http\Requests\V1;

use App\Models\Checkpoint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('visitation'));
    }

    public function rules(): array
    {
        return [
            // Checkpoint code, e.g. "triage", so clients need not know IDs
            'checkpoint' => ['required', 'string', Rule::exists('checkpoint', 'code')],
            // True when the current checkpoint is being left without service
            'skipped' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function target(): Checkpoint
    {
        return Checkpoint::where('code', $this->validated('checkpoint'))->firstOrFail();
    }
}
