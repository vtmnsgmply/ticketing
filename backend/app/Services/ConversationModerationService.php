<?php

namespace App\Services;

use App\Data\ModerationResult;
use App\Models\BlockedWord;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Repository\AdminConfigRepository;
use App\Repository\BlockedWordRepository;
use App\Repository\ModerationEventRepository;
use Illuminate\Support\Facades\Crypt;

class ConversationModerationService
{
    private const SEVERITY_WEIGHT = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
    private const ACTION_WEIGHT = ['mask' => 1, 'flag' => 2, 'mask_flag' => 3, 'block' => 4];

    public function __construct(
        private readonly BlockedWordRepository $blockedWords,
        private readonly ModerationEventRepository $events,
        private readonly AdminConfigRepository $config,
        private readonly NotificationService $notifications,
        private readonly TextNormalizationService $normalizer,
        private readonly ProfanityMatcherService $matcher,
    ) {
    }

    public function moderate(string $content, ?User $actor = null): ModerationResult
    {
        if ($this->setting('conversation_moderation_enabled', '1') !== '1' || trim($content) === '') {
            return new ModerationResult($content, $content, false, [], 'low', 'mask', false, false);
        }

        $filtered = $content;
        $normalized = $this->normalizer->normalize($content);
        $matchedTerms = $this->matcher->match($normalized);
        $highestSeverity = 'low';
        $action = $this->setting('conversation_moderation_default_action', 'mask_flag') ?: 'mask_flag';

        foreach ($matchedTerms as $match) {
            if (self::SEVERITY_WEIGHT[$match['severity']] > self::SEVERITY_WEIGHT[$highestSeverity]) {
                $highestSeverity = $match['severity'];
            }
            if (self::ACTION_WEIGHT[$match['action']] > self::ACTION_WEIGHT[$action]) {
                $action = $match['action'];
            }
        }

        $containsViolation = $matchedTerms !== [];
        if ($containsViolation && $this->setting('conversation_moderation_block_prohibited', '1') === '1') {
            $action = 'block';
        }
        $shouldBlock = $containsViolation && $action === 'block';
        $shouldFlag = $containsViolation && in_array($action, ['flag', 'mask_flag', 'block'], true);

        return new ModerationResult($content, $filtered, $containsViolation, $matchedTerms, $highestSeverity, $action, $shouldBlock, $shouldFlag);
    }

    public function recordEvent(Ticket $ticket, User $user, ModerationResult $result, ?TicketMessage $message = null): void
    {
        if (! $result->containsViolation || $this->setting('conversation_moderation_log_events', '1') !== '1') {
            return;
        }

        $event = $this->events->create([
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $message?->id,
            'user_id' => $user->id,
            'user_role' => $user->role?->slug,
            'action' => $result->action,
            'severity' => $result->highestSeverity,
            'matched_terms' => $result->matchedTerms,
            'original_content_encrypted' => $this->setting('conversation_moderation_store_original', '0') === '1'
                ? Crypt::encryptString($result->originalText)
                : null,
            'filtered_content' => $result->filteredText,
            'review_status' => $result->shouldFlag ? 'pending' : 'not_required',
        ]);

        if ($this->shouldNotifyManagers($result->highestSeverity)) {
            $this->notifications->moderationAlert($event);
        }
    }

    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $this->normalizer->normalizeTerm($value);
    }

    private function setting(string $key, string $default): string
    {
        return $this->config->settingValue($key) ?? $default;
    }

    private function shouldNotifyManagers(string $severity): bool
    {
        return match ($severity) {
            'critical' => $this->setting('conversation_moderation_notify_manager_critical', '1') === '1',
            'high' => $this->setting('conversation_moderation_notify_manager_high', '0') === '1',
            default => false,
        };
    }
}
