<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockedWordRequest;
use App\Http\Requests\Admin\BlockedWordVariantRequest;
use App\Http\Requests\Admin\ModerationSettingsRequest;
use App\Models\BlockedWord;
use App\Models\BlockedWordVariant;
use App\Repository\AdminConfigRepository;
use App\Repository\BlockedWordRepository;
use App\Repository\BlockedWordVariantRepository;
use App\Repository\ModerationEventRepository;
use App\Services\AuditLogService;
use App\Services\ConversationModerationService;
use App\Support\AuditAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminModerationController extends Controller
{
    private const SETTING_KEYS = [
        'conversation_moderation_enabled',
        'conversation_moderation_default_action',
        'conversation_moderation_mask_character',
        'conversation_moderation_store_original',
        'conversation_moderation_log_events',
        'conversation_moderation_notify_manager_high',
        'conversation_moderation_notify_manager_critical',
        'conversation_moderation_block_prohibited',
        'conversation_moderation_unicode_normalization',
        'conversation_moderation_accent_folding',
        'conversation_moderation_leetspeak_detection',
        'conversation_moderation_invisible_stripping',
        'conversation_moderation_separator_detection',
        'conversation_moderation_repeat_normalization',
        'conversation_moderation_compressed_variants',
        'conversation_moderation_fuzzy_matching',
    ];

    public function __construct(
        private readonly AdminConfigRepository $config,
        private readonly BlockedWordRepository $blockedWords,
        private readonly BlockedWordVariantRepository $variants,
        private readonly ModerationEventRepository $events,
        private readonly ConversationModerationService $moderation,
        private readonly AuditLogService $audit,
    ) {
    }

    public function settings(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Moderation settings retrieved.', 'data' => ['settings' => $this->settingsPayload()]]);
    }

    public function updateSettings(ModerationSettingsRequest $request): JsonResponse
    {
        foreach ($request->validated() as $key => $value) {
            $this->config->upsertSetting($key, $value, is_bool($value) ? 'boolean' : 'string', false);
        }
        $this->audit->record($request->user(), AuditAction::MODERATION_SETTINGS_UPDATED, 'system_settings', null, null, array_keys($request->validated()), $request);

        return response()->json(['success' => true, 'message' => 'Moderation settings updated.', 'data' => ['settings' => $this->settingsPayload()]]);
    }

    public function blockedWords(Request $request): JsonResponse
    {
        $paginator = $this->blockedWords->paginate($request->query());

        return response()->json(['success' => true, 'message' => 'Blocked terms retrieved.', 'data' => [
            'blocked_words' => $paginator->items(),
            'pagination' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()],
        ]]);
    }

    public function storeBlockedWord(BlockedWordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['normalized_word'] = $this->moderation->normalize($data['word']);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $word = $this->blockedWords->create($data);
        $this->audit->record($request->user(), AuditAction::MODERATION_WORD_CREATED, 'blocked_word', $word->id, null, $this->safeWordAudit($word), $request);

        return response()->json(['success' => true, 'message' => 'Blocked term created.', 'data' => ['blocked_word' => $word->load('variants')]], 201);
    }

    public function updateBlockedWord(BlockedWordRequest $request, BlockedWord $blockedWord): JsonResponse
    {
        $old = $this->safeWordAudit($blockedWord);
        $data = $request->validated();
        $data['normalized_word'] = $this->moderation->normalize($data['word']);
        $data['updated_by'] = $request->user()->id;
        $word = $this->blockedWords->update($blockedWord, $data);
        $this->audit->record($request->user(), AuditAction::MODERATION_WORD_UPDATED, 'blocked_word', $word->id, $old, $this->safeWordAudit($word), $request);

        return response()->json(['success' => true, 'message' => 'Blocked term updated.', 'data' => ['blocked_word' => $word->load('variants')]]);
    }

    public function variants(BlockedWord $blockedWord): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Blocked term variants retrieved.', 'data' => [
            'variants' => $blockedWord->variants()->latest()->get(),
        ]]);
    }

    public function storeVariant(BlockedWordVariantRequest $request, BlockedWord $blockedWord): JsonResponse
    {
        $data = $request->validated();
        $data['normalized_variant'] = $this->moderation->normalize($data['variant']);
        $variant = $this->variants->create($blockedWord, $data);
        $this->audit->record($request->user(), AuditAction::MODERATION_WORD_UPDATED, 'blocked_word', $blockedWord->id, null, ['variant_type' => $variant->variant_type, 'is_active' => $variant->is_active], $request);

        return response()->json(['success' => true, 'message' => 'Blocked term variant created.', 'data' => ['variant' => $variant]], 201);
    }

    public function updateVariant(BlockedWordVariantRequest $request, BlockedWord $blockedWord, BlockedWordVariant $variant): JsonResponse
    {
        if ((int) $variant->blocked_word_id !== (int) $blockedWord->id) {
            return response()->json(['success' => false, 'message' => 'Blocked term variant not found.'], 404);
        }

        $data = $request->validated();
        $data['normalized_variant'] = $this->moderation->normalize($data['variant']);
        $updated = $this->variants->update($variant, $data);
        $this->audit->record($request->user(), AuditAction::MODERATION_WORD_UPDATED, 'blocked_word', $blockedWord->id, null, ['variant_id' => $updated->id, 'variant_type' => $updated->variant_type, 'is_active' => $updated->is_active], $request);

        return response()->json(['success' => true, 'message' => 'Blocked term variant updated.', 'data' => ['variant' => $updated]]);
    }

    public function deleteVariant(Request $request, BlockedWord $blockedWord, BlockedWordVariant $variant): JsonResponse
    {
        if ((int) $variant->blocked_word_id !== (int) $blockedWord->id) {
            return response()->json(['success' => false, 'message' => 'Blocked term variant not found.'], 404);
        }

        $variantId = $variant->id;
        $this->variants->delete($variant);
        $this->audit->record($request->user(), AuditAction::MODERATION_WORD_UPDATED, 'blocked_word', $blockedWord->id, null, ['deleted_variant_id' => $variantId], $request);

        return response()->json(['success' => true, 'message' => 'Blocked term variant deleted.', 'data' => []]);
    }

    public function enableBlockedWord(Request $request, BlockedWord $blockedWord): JsonResponse
    {
        $word = $this->blockedWords->setActive($blockedWord, true, $request->user()->id);
        $this->audit->record($request->user(), AuditAction::MODERATION_WORD_UPDATED, 'blocked_word', $word->id, null, ['is_active' => true], $request);

        return response()->json(['success' => true, 'message' => 'Blocked term enabled.', 'data' => ['blocked_word' => $word]]);
    }

    public function disableBlockedWord(Request $request, BlockedWord $blockedWord): JsonResponse
    {
        $word = $this->blockedWords->setActive($blockedWord, false, $request->user()->id);
        $this->audit->record($request->user(), AuditAction::MODERATION_WORD_DISABLED, 'blocked_word', $word->id, null, ['is_active' => false], $request);

        return response()->json(['success' => true, 'message' => 'Blocked term disabled.', 'data' => ['blocked_word' => $word]]);
    }

    public function events(Request $request): JsonResponse
    {
        $paginator = $this->events->paginateForReview($request->user(), $request->query());

        return response()->json(['success' => true, 'message' => 'Moderation events retrieved.', 'data' => [
            'events' => collect($paginator->items())->map(fn ($event) => $this->eventPayload($event))->values(),
            'pagination' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()],
        ]]);
    }

    public function event(Request $request, int $event): JsonResponse
    {
        $record = $this->events->findForUser($event, $request->user());
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Moderation event not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Moderation event retrieved.', 'data' => ['event' => $this->eventPayload($record)]]);
    }

    public function review(Request $request, int $event): JsonResponse
    {
        return $this->reviewAction($request, $event, 'reviewed', AuditAction::MODERATION_EVENT_REVIEWED, 'Moderation event reviewed.');
    }

    public function dismiss(Request $request, int $event): JsonResponse
    {
        return $this->reviewAction($request, $event, 'dismissed', AuditAction::MODERATION_EVENT_REVIEWED, 'Moderation event dismissed.');
    }

    public function escalate(Request $request, int $event): JsonResponse
    {
        return $this->reviewAction($request, $event, 'escalated', AuditAction::MODERATION_EVENT_ESCALATED, 'Moderation event escalated.');
    }

    private function reviewAction(Request $request, int $event, string $status, string $auditAction, string $message): JsonResponse
    {
        $record = $this->events->findForUser($event, $request->user());
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Moderation event not found.'], 404);
        }
        $updated = $this->events->updateReview($record, $request->user(), $status);
        $this->audit->record($request->user(), $auditAction, 'conversation_moderation_event', $updated->id, null, ['review_status' => $status], $request);

        return response()->json(['success' => true, 'message' => $message, 'data' => ['event' => $this->eventPayload($updated)]]);
    }

    private function settingsPayload(): array
    {
        $payload = [];
        foreach (self::SETTING_KEYS as $key) {
            $payload[$key] = $this->config->settingValue($key);
        }

        return $payload;
    }

    private function safeWordAudit(BlockedWord $word): array
    {
        return $word->only(['id', 'severity', 'action', 'is_active']);
    }

    private function eventPayload($event): array
    {
        return [
            'id' => $event->id,
            'ticket_id' => $event->ticket_id,
            'ticket_message_id' => $event->ticket_message_id,
            'ticket' => $event->ticket?->only(['id', 'ticket_number', 'subject', 'department_id']),
            'user' => $event->user?->only(['id', 'name', 'email']),
            'user_role' => $event->user_role,
            'action' => $event->action,
            'severity' => $event->severity,
            'matched_term_count' => collect($event->matched_terms ?? [])->sum('count'),
            'filtered_content' => $event->filtered_content,
            'review_status' => $event->review_status,
            'reviewer' => $event->reviewer?->only(['id', 'name', 'email']),
            'reviewed_at' => $event->reviewed_at?->toISOString(),
            'created_at' => $event->created_at?->toISOString(),
        ];
    }
}
