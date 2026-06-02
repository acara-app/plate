<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('workflow_relationships');
        Schema::dropIfExists('workflow_exceptions');
        Schema::dropIfExists('workflow_timers');
        Schema::dropIfExists('workflow_signals');
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflows');
    }

    public function down(): void
    {
        //
    }
};
