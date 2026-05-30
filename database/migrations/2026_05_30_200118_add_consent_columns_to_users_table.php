<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('terms_accepted_at')->nullable()->after('accepted_disclaimer_at');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_accepted_at');
            $table->string('consent_version')->nullable()->after('privacy_accepted_at');
        });
    }
};
