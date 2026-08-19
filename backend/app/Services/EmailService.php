<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailService
{
    public function sendNotification(Notification $notification): ?string
    {
        $recipient = $notification->user;
        if ($recipient === null || blank($recipient->email)) {
            return 'Recipient email is unavailable.';
        }

        try {
            Mail::raw($notification->message, function ($message) use ($notification, $recipient): void {
                $message->to($recipient->email)->subject($notification->title);
            });
        } catch (Throwable $exception) {
            return preg_replace('/password|secret|token|key/i', '[redacted]', $exception->getMessage()) ?: 'Email delivery failed.';
        }

        return null;
    }
}
