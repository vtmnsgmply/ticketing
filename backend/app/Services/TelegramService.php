<?php

namespace App\Services;

use App\Events\TicketConversationUpdated;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Repository\TicketMessageRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Support\NotificationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TicketRepository $tickets,
        private readonly TicketMessageRepository $messages,
        private readonly TicketActivityService $activities,
        private readonly NotificationService $notifications,
        private readonly ConversationModerationService $moderation,
    ) {
    }

    public function handleWebhook(array $payload): array
    {
        $chatId = data_get($payload, 'message.chat.id');
        $text = trim((string) data_get($payload, 'message.text', ''));

        if ($chatId === null || $text === '') {
            return ['handled' => false];
        }

        $chatId = (string) $chatId;
        if (str_starts_with($text, '/start')) {
            $this->sendText($chatId, "Your Telegram Chat ID is {$chatId}.\nAdd this ID to your ticketing profile to receive ticket updates.");

            return ['handled' => true, 'chat_id' => $chatId];
        }

        return $this->handleIncomingTicketReply($chatId, $text);
    }

    private function handleIncomingTicketReply(string $chatId, string $text): array
    {
        $customer = $this->users->findActiveCustomerByTelegramChatId($chatId);

        if ($customer === null) {
            $this->sendText($chatId, "This Telegram chat is not linked to an active customer profile.\nAdd chat ID {$chatId} to your ticketing profile first.");

            return ['handled' => true, 'chat_id' => $chatId, 'linked' => false];
        }

        [$ticketNumber, $message] = $this->parseTicketReply($text);

        if ($message === '') {
            $this->sendText($chatId, "Please include a message for the ticket.\nExample: /ticket TKT-10001 I still need help.");

            return ['handled' => true, 'chat_id' => $chatId, 'linked' => true, 'posted' => false];
        }

        $ticket = $ticketNumber === null
            ? $this->singleActiveTicketForPlainReply($customer, $chatId)
            : $this->tickets->findActiveByNumberForCustomer($ticketNumber, $customer);

        if ($ticket === null) {
            if ($ticketNumber !== null) {
                $this->sendText($chatId, "Ticket {$ticketNumber} was not found or is already closed.");
            }

            return ['handled' => true, 'chat_id' => $chatId, 'linked' => true, 'posted' => false];
        }

        try {
            $moderation = $this->moderation->moderate($message, $customer);
        } catch (Throwable) {
            $this->sendText($chatId, 'We could not process your message safely. Please try again.');

            return ['handled' => true, 'chat_id' => $chatId, 'linked' => true, 'posted' => false];
        }

        if ($moderation->shouldBlock) {
            $this->moderation->recordEvent($ticket, $customer, $moderation);
            $this->sendText($chatId, 'Your message contains language that is not permitted. Please revise it and try again.');

            return ['handled' => true, 'chat_id' => $chatId, 'linked' => true, 'posted' => false, 'blocked' => true];
        }

        $created = DB::transaction(function () use ($ticket, $customer, $moderation, $chatId): TicketMessage {
            $created = $this->messages->create([
                'ticket_id' => $ticket->id,
                'user_id' => $customer->id,
                'message' => trim($moderation->filteredText),
                'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
            ]);

            $this->moderation->recordEvent($ticket, $customer, $moderation, $created);
            $this->activities->record($ticket, $customer, 'customer_replied', null, null, [
                'source' => 'telegram',
                'telegram_chat_id' => $chatId,
            ]);
            $this->notifications->ticketEvent(NotificationType::CUSTOMER_REPLIED, $ticket, $customer);

            return $created;
        });

        broadcast(new TicketConversationUpdated($ticket, $customer, 'message.created', (int) $created->id));
        $this->sendText($chatId, "Your message was added to ticket {$ticket->ticket_number}.");

        return [
            'handled' => true,
            'chat_id' => $chatId,
            'linked' => true,
            'posted' => true,
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'message_id' => $created->id,
        ];
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

        try {
            return Http::connectTimeout(2)->timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => trim($chatId),
                'text' => $text,
                'disable_web_page_preview' => true,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Telegram message delivery error.', [
                'chat_id' => trim($chatId),
                'error' => $exception->getMessage(),
            ]);

            return Http::response(['ok' => false], 500);
        }
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

    /**
     * @return array{0: string|null, 1: string}
     */
    private function parseTicketReply(string $text): array
    {
        $text = trim($text);

        if (preg_match('/^\/ticket(?:@\w+)?\s+([A-Za-z]+-\d+)\s+(.+)$/s', $text, $matches) === 1) {
            return [strtoupper($matches[1]), trim($matches[2])];
        }

        if (preg_match('/^\/ticket(?:@\w+)?(?:\s+.*)?$/s', $text) === 1) {
            return [null, ''];
        }

        if (preg_match('/^\[?([A-Za-z]+-\d+)\]?\s*[:\-]\s*(.+)$/s', $text, $matches) === 1) {
            return [strtoupper($matches[1]), trim($matches[2])];
        }

        if (preg_match('/^([A-Za-z]+-\d+)\s+(.+)$/s', $text, $matches) === 1) {
            return [strtoupper($matches[1]), trim($matches[2])];
        }

        return [null, $text];
    }

    private function singleActiveTicketForPlainReply(User $customer, string $chatId): ?Ticket
    {
        $tickets = $this->tickets->activeForCustomer($customer, 6);

        if ($tickets->count() === 1) {
            return $tickets->first();
        }

        if ($tickets->isEmpty()) {
            $this->sendText($chatId, 'You do not have an active ticket available for Telegram replies.');

            return null;
        }

        $lines = $tickets
            ->map(fn (Ticket $ticket) => "{$ticket->ticket_number} - {$ticket->subject}")
            ->implode("\n");

        $this->sendText($chatId, "You have multiple active tickets. Reply with the ticket number first:\n/ticket TKT-10001 your message\n\nActive tickets:\n{$lines}");

        return null;
    }

    private function formatStatus(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}
