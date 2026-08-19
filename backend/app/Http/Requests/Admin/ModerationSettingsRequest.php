<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ModerationSettingsRequest extends ApiRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('administrator') ?? false; }

    public function rules(): array
    {
        return [
            'conversation_moderation_enabled' => ['required', 'boolean'],
            'conversation_moderation_default_action' => ['required', Rule::in(['mask', 'block', 'flag', 'mask_flag'])],
            'conversation_moderation_mask_character' => ['required', 'string', 'max:1'],
            'conversation_moderation_store_original' => ['required', 'boolean'],
            'conversation_moderation_log_events' => ['required', 'boolean'],
            'conversation_moderation_notify_manager_high' => ['required', 'boolean'],
            'conversation_moderation_notify_manager_critical' => ['required', 'boolean'],
            'conversation_moderation_block_prohibited' => ['required', 'boolean'],
            'conversation_moderation_unicode_normalization' => ['required', 'boolean'],
            'conversation_moderation_accent_folding' => ['required', 'boolean'],
            'conversation_moderation_leetspeak_detection' => ['required', 'boolean'],
            'conversation_moderation_invisible_stripping' => ['required', 'boolean'],
            'conversation_moderation_separator_detection' => ['required', 'boolean'],
            'conversation_moderation_repeat_normalization' => ['required', 'boolean'],
            'conversation_moderation_compressed_variants' => ['required', 'boolean'],
            'conversation_moderation_fuzzy_matching' => ['required', 'boolean'],
        ];
    }
}
