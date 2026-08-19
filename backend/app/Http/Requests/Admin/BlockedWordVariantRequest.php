<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;
use App\Models\BlockedWordVariant;
use Illuminate\Validation\Rule;

class BlockedWordVariantRequest extends ApiRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('administrator') ?? false; }

    public function rules(): array
    {
        return [
            'variant' => ['required', 'string', 'max:190'],
            'variant_type' => ['required', Rule::in(BlockedWordVariant::TYPES)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
