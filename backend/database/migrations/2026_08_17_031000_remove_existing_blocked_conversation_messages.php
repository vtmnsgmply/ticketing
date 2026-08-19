<?php

use App\Services\ConversationModerationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moderation = app(ConversationModerationService::class);
        $now = now();

        DB::table('ticket_messages')
            ->whereNull('deleted_at')
            ->whereNotNull('message')
            ->orderBy('id')
            ->chunkById(100, function ($messages) use ($moderation, $now): void {
                foreach ($messages as $message) {
                    $result = $moderation->moderate((string) $message->message);
                    if (! $result->shouldBlock) {
                        continue;
                    }

                    DB::table('ticket_messages')
                        ->where('id', $message->id)
                        ->update([
                            'message' => 'Message removed by moderation.',
                            'deleted_by' => $message->user_id,
                            'deleted_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            });

        Cache::forget('conversation_moderation.active_blocked_words');
    }

    public function down(): void
    {
        // Irreversible by design: removed content should not be restored.
    }
};
