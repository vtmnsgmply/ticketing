<?php

use App\Services\TextNormalizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blocked_word_variants')) {
            Schema::create('blocked_word_variants', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('blocked_word_id')->constrained('blocked_words')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('variant', 190);
                $table->string('normalized_variant', 190)->index();
                $table->string('variant_type', 40)->default('manual')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->unique(['blocked_word_id', 'normalized_variant']);
            });
        }

        $settings = [
            ['key' => 'conversation_moderation_block_prohibited', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_unicode_normalization', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_accent_folding', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_leetspeak_detection', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_invisible_stripping', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_separator_detection', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_repeat_normalization', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_compressed_variants', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
            ['key' => 'conversation_moderation_fuzzy_matching', 'value' => '1', 'value_type' => 'boolean', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(['key' => $setting['key']], $setting);
        }

        $normalizer = app(TextNormalizationService::class);
        $variants = [
            'fuck' => [
                ['fck', 'compressed'],
                ['fuk', 'common_misspelling'],
                ['fucking', 'morphological'],
                ['fcking', 'compressed'],
                ['fucker', 'morphological'],
                ['fucked', 'morphological'],
            ],
            'shit' => [
                ['sht', 'compressed'],
                ['sh1t', 'common_misspelling'],
                ['shitty', 'morphological'],
                ['shitting', 'morphological'],
            ],
            'bitch' => [
                ['btch', 'compressed'],
                ['b1tch', 'common_misspelling'],
                ['bitching', 'morphological'],
            ],
            'putang ina' => [
                ['putangina', 'compressed'],
                ['ptngina', 'compressed'],
                ['pota', 'common_misspelling'],
                ['tangina', 'common_misspelling'],
            ],
            'gago' => [
                ['gagu', 'common_misspelling'],
            ],
            'pakshet' => [
                ['pakshit', 'common_misspelling'],
                ['pak shet', 'common_misspelling'],
            ],
        ];

        foreach ($variants as $word => $wordVariants) {
            $blockedWord = DB::table('blocked_words')->where('normalized_word', $normalizer->normalizeTerm($word))->first();
            if (! $blockedWord) {
                continue;
            }

            foreach ($wordVariants as [$variant, $type]) {
                DB::table('blocked_word_variants')->updateOrInsert(
                    [
                        'blocked_word_id' => $blockedWord->id,
                        'normalized_variant' => $normalizer->normalizeTerm($variant),
                    ],
                    [
                        'variant' => $variant,
                        'variant_type' => $type,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        Cache::forget('conversation_moderation.active_blocked_words');
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'conversation_moderation_block_prohibited',
            'conversation_moderation_unicode_normalization',
            'conversation_moderation_accent_folding',
            'conversation_moderation_leetspeak_detection',
            'conversation_moderation_invisible_stripping',
            'conversation_moderation_separator_detection',
            'conversation_moderation_repeat_normalization',
            'conversation_moderation_compressed_variants',
            'conversation_moderation_fuzzy_matching',
        ])->delete();

        Schema::dropIfExists('blocked_word_variants');
        Cache::forget('conversation_moderation.active_blocked_words');
    }
};
