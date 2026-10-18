<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfakit_webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            // The tenant that owns the endpoint (null: a panel-wide endpoint).
            $table->string('tenant_type')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->string('created_by_id')->nullable();
            $table->text('url');
            $table->string('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->json('events');
            // Encrypted with the application key.
            $table->text('secret');
            $table->text('previous_secret')->nullable();
            $table->timestamp('previous_secret_expires_at')->nullable();
            $table->text('headers')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('disabled_at')->nullable();
            $table->string('disabled_reason')->nullable();
            $table->timestamp('last_delivery_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_type', 'tenant_id']);
        });

        Schema::create('alfakit_webhook_events', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('name')->index();
            $table->string('tenant_type')->nullable();
            $table->string('tenant_id')->nullable();
            $table->longText('payload');
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['tenant_type', 'tenant_id']);
        });

        Schema::create('alfakit_webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('alfakit_webhook_endpoints')->cascadeOnDelete();
            $table->string('event_id', 64);
            $table->foreign('event_id')->references('id')->on('alfakit_webhook_events')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('status', 20)->index();
            $table->boolean('is_test')->default(false);
            $table->unsignedBigInteger('replay_of_id')->nullable();
            $table->text('url')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['endpoint_id', 'created_at']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfakit_webhook_deliveries');
        Schema::dropIfExists('alfakit_webhook_events');
        Schema::dropIfExists('alfakit_webhook_endpoints');
    }
};
