<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_approvals', function (Blueprint $table): void {
            $table->timestamp('notified_at')->nullable()->after('executed_at');
        });
    }
};
