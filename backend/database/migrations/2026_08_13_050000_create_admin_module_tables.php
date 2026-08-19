<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('department_users')) {
            Schema::create('department_users', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
                $table->timestamp('created_at')->nullable();
                $table->unique(['department_id', 'user_id']);
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 190)->unique();
                $table->longText('value')->nullable();
                $table->string('value_type', 40)->default('string');
                $table->boolean('is_public')->default(false)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
                $table->string('action', 150);
                $table->string('entity_type', 150)->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index('user_id');
                $table->index('action');
                $table->index(['entity_type', 'entity_id']);
                $table->index('created_at');
            });
        }

        foreach ([
            ['key' => 'ticket_prefix', 'value' => 'TKT', 'value_type' => 'string', 'is_public' => true],
            ['key' => 'ticket_start_number', 'value' => '10000', 'value_type' => 'integer', 'is_public' => false],
            ['key' => 'attachment_max_size_mb', 'value' => '10', 'value_type' => 'integer', 'is_public' => true],
            ['key' => 'notification_web_enabled', 'value' => '1', 'value_type' => 'boolean', 'is_public' => true],
            ['key' => 'notification_email_enabled', 'value' => '1', 'value_type' => 'boolean', 'is_public' => true],
            ['key' => 'allow_customer_reopen_resolved', 'value' => '1', 'value_type' => 'boolean', 'is_public' => true],
            ['key' => 'customer_reopen_limit', 'value' => '3', 'value_type' => 'integer', 'is_public' => true],
            ['key' => 'sender_name', 'value' => null, 'value_type' => 'string', 'is_public' => false],
            ['key' => 'sender_email', 'value' => null, 'value_type' => 'string', 'is_public' => false],
            ['key' => 'reply_to_email', 'value' => null, 'value_type' => 'string', 'is_public' => false],
            ['key' => 'smtp_host', 'value' => null, 'value_type' => 'string', 'is_public' => false],
            ['key' => 'smtp_port', 'value' => null, 'value_type' => 'integer', 'is_public' => false],
            ['key' => 'smtp_username', 'value' => null, 'value_type' => 'string', 'is_public' => false],
            ['key' => 'smtp_password', 'value' => null, 'value_type' => 'secret', 'is_public' => false],
            ['key' => 'encryption', 'value' => 'tls', 'value_type' => 'string', 'is_public' => false],
        ] as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('department_users');
    }
};
