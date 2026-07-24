<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_drafts', function (Blueprint $table): void {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->unsignedSmallInteger('schema_version');
            $table->string('source');
            $table->json('payload');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->uuid('health_group_id')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }
};
