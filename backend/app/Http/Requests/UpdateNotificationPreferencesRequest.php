<?php

namespace App\Http\Requests;

class UpdateNotificationPreferencesRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'web_notifications_enabled' => ['required', 'boolean'],
            'email_notifications_enabled' => ['required', 'boolean'],
        ];
    }
}
