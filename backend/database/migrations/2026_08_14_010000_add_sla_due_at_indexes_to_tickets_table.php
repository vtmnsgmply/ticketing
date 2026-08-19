<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (! Schema::hasIndex('tickets', 'tickets_first_response_due_at_index')) {
                $table->index('first_response_due_at');
            }

            if (! Schema::hasIndex('tickets', 'tickets_resolution_due_at_index')) {
                $table->index('resolution_due_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (Schema::hasIndex('tickets', 'tickets_first_response_due_at_index')) {
                $table->dropIndex('tickets_first_response_due_at_index');
            }

            if (Schema::hasIndex('tickets', 'tickets_resolution_due_at_index')) {
                $table->dropIndex('tickets_resolution_due_at_index');
            }
        });
    }
};
