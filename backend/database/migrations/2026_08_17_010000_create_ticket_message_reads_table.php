<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_message_reads')) {
            Schema::create('ticket_message_reads', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_message_id')->constrained('ticket_messages')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->timestamp('read_at');
                $table->timestamps();
                $table->unique(['ticket_message_id', 'user_id']);
                $table->index(['user_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_message_reads');
    }
};
