<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('diabetes_logs', 'health_entries');

        Schema::table('health_entries', function (Blueprint $table): void {
            $table->string('source')->nullable()->after('notes');
        });
    }
};
