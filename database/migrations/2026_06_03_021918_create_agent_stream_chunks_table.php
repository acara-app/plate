<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_stream_chunks', function (Blueprint $table): void {
            $table->id();
            $table->string('run_id', 26);
            $table->unsignedInteger('sequence');
            $table->string('type');
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['run_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_stream_chunks');
    }
};
