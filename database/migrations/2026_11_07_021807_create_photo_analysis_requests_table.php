<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_analysis_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->uuid('retry_request_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('guest_hash', 64)->nullable()->index();
            $table->text('guest_id')->nullable();
            $table->string('fingerprint', 64);
            $table->string('source');
            $table->string('mime_type');
            $table->longText('image_base64')->nullable();
            $table->text('draft_token')->nullable();
            $table->string('status');
            $table->string('failure_code')->nullable();
            $table->timestamp('deadline_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_analysis_requests');
    }
};
