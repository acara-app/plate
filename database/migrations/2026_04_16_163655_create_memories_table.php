<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isPostgres = DB::connection()->getDriverName() === 'pgsql';

        if ($isPostgres) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::create('memories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('content');
            $table->json('metadata')->nullable();
            $table->json('categories')->nullable();
            $table->unsignedTinyInteger('importance')->default(5);
            $table->string('source')->nullable();

            $table->boolean('is_archived')->default(false);
            $table->timestamp('expires_at')->nullable();

            $table->unsignedInteger('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();

            $table->json('consolidated_from')->nullable();
            $table->ulid('consolidated_into')->nullable();
            $table->timestamp('consolidated_at')->nullable();
            $table->unsignedSmallInteger('consolidation_generation')->default(0);

            $table->boolean('is_pinned')->default(false);
            $table->string('memory_type')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_archived']);
            $table->index(['user_id', 'importance']);
            $table->index(['user_id', 'is_pinned']);
            $table->index(['user_id', 'memory_type']);
            $table->index('expires_at');
            $table->index('consolidated_at');
        });

        if ($isPostgres) {
            $dimensions = (int) config('memory.embeddings.dimensions', 1536);

            DB::statement(sprintf('ALTER TABLE memories ADD COLUMN embedding vector(%d)', $dimensions));
            DB::statement('CREATE INDEX memories_embedding_idx ON memories USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        } else {
            Schema::table('memories', function (Blueprint $table): void {
                $table->longText('embedding')->nullable();
            });
        }
    }
};
