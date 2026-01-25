<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropForeign(['lifestyle_id']);
            $table->dropColumn('lifestyle_id');
        });

        Schema::dropIfExists('lifestyles');
    }
};
