<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;
use App\Models\BlockedWord;
use Illuminate\Validation\Rule;

class BlockedWordRequest extends ApiRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('administrator') ?? false; }

    public function rules(): array
    {
        return [
            'word' => ['required', 'string', 'max:190'],
            'severity' => ['required', Rule::in(BlockedWord::SEVERITIES)],
            'action' => ['required', Rule::in(BlockedWord::ACTIONS)],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
