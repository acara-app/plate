<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profile_dietary_preference', function (Blueprint $table): void {
            $table->string('severity')->nullable()->after('dietary_preference_id')->comment('Allergy severity: mild, moderate, severe');
            $table->text('notes')->nullable()->after('severity');
        });
    }
};
