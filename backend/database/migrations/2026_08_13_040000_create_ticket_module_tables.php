<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 150);
                $table->string('slug', 150)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
                $table->string('name', 150);
                $table->string('slug', 150);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->unique(['department_id', 'slug']);
            });
        }

        if (! Schema::hasTable('priorities')) {
            Schema::create('priorities', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sla_rules')) {
            Schema::create('sla_rules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('priority_id')->unique()->constrained('priorities')->cascadeOnDelete()->cascadeOnUpdate();
                $table->unsignedInteger('first_response_minutes');
                $table->unsignedInteger('resolution_minutes');
                $table->boolean('pause_on_waiting_customer')->default(true);
                $table->boolean('use_business_hours')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table): void {
                $table->id();
                $table->string('ticket_number', 50)->unique();
                $table->foreignId('customer_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('subject', 255);
                $table->longText('description');
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('priority_id')->constrained('priorities')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('status', 40)->default('new');
                $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->dateTime('first_response_due_at')->nullable();
                $table->dateTime('resolution_due_at')->nullable();
                $table->dateTime('first_responded_at')->nullable();
                $table->dateTime('resolved_at')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->dateTime('cancelled_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'priority_id']);
                $table->index(['department_id', 'status']);
                $table->index(['assigned_agent_id', 'status']);
            });
        }

        if (! Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
                $table->longText('message');
                $table->string('message_type', 40);
                $table->timestamps();
                $table->index(['ticket_id', 'created_at']);
                $table->index('message_type');
            });
        }

        if (! Schema::hasTable('ticket_attachments')) {
            Schema::create('ticket_attachments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('original_name', 255);
                $table->string('stored_name', 255);
                $table->string('file_path', 500);
                $table->string('mime_type', 150);
                $table->unsignedBigInteger('file_size');
                $table->timestamp('created_at')->nullable();
                $table->index('ticket_id');
            });
        }

        if (! Schema::hasTable('ticket_activities')) {
            Schema::create('ticket_activities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->string('action', 100);
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['ticket_id', 'created_at']);
                $table->index('action');
            });
        }

        if (! Schema::hasTable('ticket_number_sequences')) {
            Schema::create('ticket_number_sequences', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('current_value')->default(10000);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'ticket_activities',
            'ticket_attachments',
            'ticket_messages',
            'tickets',
            'ticket_number_sequences',
            'sla_rules',
            'priorities',
            'categories',
            'departments',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
