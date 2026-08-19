<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blocked_words')) {
            Schema::create('blocked_words', function (Blueprint $table): void {
                $table->id();
                $table->string('word', 190);
                $table->string('normalized_word', 190)->index();
                $table->string('severity', 20)->default('medium')->index();
                $table->string('action', 20)->default('mask_flag')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamps();
                $table->unique('normalized_word');
            });
        }

        if (! Schema::hasTable('conversation_moderation_events')) {
            Schema::create('conversation_moderation_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('user_role', 50)->nullable();
                $table->string('action', 20)->index();
                $table->string('severity', 20)->index();
                $table->json('matched_terms');
                $table->text('original_content_encrypted')->nullable();
                $table->text('filtered_content')->nullable();
                $table->string('review_status', 20)->default('pending')->index();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['ticket_id', 'review_status']);
            });
        }

        $settings = [
            ['key' => 'conversation_moderation_enabled', 'value' => '1', 'value_type' => 'boolean', 'is_public' => true],
            ['key' => 'conversation_moderation_default_action', 'value' => 'mask_flag', 'value_type' => 'string', 'is_public' => true],
            ['key' => 'conversation_moderation_mask_character', 'value' => '*', 'value_type' => 'string', 'is_public' => true],
            ['key' => 'conversation_moderation_store_original', 'value' => '0', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_log_events', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_notify_manager_high', 'value' => '0', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_notify_manager_critical', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(['key' => $setting['key']], $setting);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'conversation_moderation_enabled',
            'conversation_moderation_default_action',
            'conversation_moderation_mask_character',
            'conversation_moderation_store_original',
            'conversation_moderation_log_events',
            'conversation_moderation_notify_manager_high',
            'conversation_moderation_notify_manager_critical',
        ])->delete();

        Schema::dropIfExists('conversation_moderation_events');
        Schema::dropIfExists('blocked_words');
    }
};
