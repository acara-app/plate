<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('diabetes_logs', function (Blueprint $table) {
            // Make glucose_value nullable since users can log insulin, medications, etc. without glucose
            $table->decimal('glucose_value', 5, 1)->nullable()->change();
            $table->string('glucose_reading_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diabetes_logs', function (Blueprint $table) {
            // Revert to not nullable (this may fail if there are null values)
            $table->decimal('glucose_value', 5, 1)->nullable(false)->change();
            $table->string('glucose_reading_type')->nullable(false)->change();
        });
    }
};
