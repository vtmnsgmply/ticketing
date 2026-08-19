<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramService
{
    public function handleWebhook(array $payload): array
    {
        $chatId = data_get($payload, 'message.chat.id');
        $text = trim((string) data_get($payload, 'message.text', ''));

        if ($chatId === null || ! str_starts_with($text, '/start')) {
            return ['handled' => false];
        }

        $chatId = (string) $chatId;
        $this->sendText($chatId, "Your Telegram Chat ID is {$chatId}.\nAdd this ID to your ticketing profile to receive ticket updates.");

        return ['handled' => true, 'chat_id' => $chatId];
    }

    public function sendTicketStatusChanged(User $customer, Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        $token = (string) config('services.telegram.bot_token', '');
        $chatId = $this->normalizeChatId((string) $customer->telegram_profile);

        if ($token === '' || $chatId === '') {
            return;
        }

        try {
            $response = $this->sendText($chatId, $this->statusMessage($ticket, $oldStatus, $newStatus));

            if ($response->failed()) {
                Log::warning('Telegram ticket status notification failed.', [
                    'ticket_id' => $ticket->id,
                    'customer_id' => $customer->id,
                    'status' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Telegram ticket status notification error.', [
                'ticket_id' => $ticket->id,
                'customer_id' => $customer->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function sendText(string $chatId, string $text)
    {
        $token = (string) config('services.telegram.bot_token', '');

        if ($token === '' || trim($chatId) === '') {
            return Http::response(['ok' => false], 400);
        }

        return Http::connectTimeout(2)->timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => trim($chatId),
            'text' => $text,
            'disable_web_page_preview' => true,
        ]);
    }

    private function statusMessage(Ticket $ticket, string $oldStatus, string $newStatus): string
    {
        return "Your ticket {$ticket->ticket_number} status is ".$this->formatStatus($newStatus).".";
    }

    private function normalizeChatId(string $profile): string
    {
        $profile = trim($profile);

        if ($profile === '') {
            return '';
        }

        if (preg_match('/^-?\d+$/', $profile) === 1) {
            return $profile;
        }

        return '';
    }

    private function formatStatus(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}
