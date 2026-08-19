<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table): void {
            if (! Schema::hasColumn('ticket_attachments', 'thumbnail_path')) {
                $table->string('thumbnail_path', 500)->nullable()->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table): void {
            if (Schema::hasColumn('ticket_attachments', 'thumbnail_path')) {
                $table->dropColumn('thumbnail_path');
            }
        });
    }
};
