<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('ticket_messages', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('message_type')->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            }

            if (! Schema::hasColumn('ticket_messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('deleted_by')->index();
            }
        });

        if (! Schema::hasTable('ticket_message_reactions')) {
            Schema::create('ticket_message_reactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_message_id')->constrained('ticket_messages')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('reaction', 32);
                $table->timestamps();
                $table->unique(['ticket_message_id', 'user_id', 'reaction'], 'ticket_message_reactions_unique');
                $table->index(['ticket_message_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_message_reactions');

        Schema::table('ticket_messages', function (Blueprint $table): void {
            if (Schema::hasColumn('ticket_messages', 'deleted_by')) {
                $table->dropConstrainedForeignId('deleted_by');
            }

            if (Schema::hasColumn('ticket_messages', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};
