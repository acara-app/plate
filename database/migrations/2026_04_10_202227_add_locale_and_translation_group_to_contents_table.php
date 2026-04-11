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
        Schema::table('contents', function (Blueprint $table): void {
            $table->string('locale', 10)->default('en');
            $table->uuid('translation_group')->nullable();
            $table->index('locale');
            $table->index('translation_group');
            $table->unique(['slug', 'locale']);
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropUnique('contents_slug_unique');
        });

        DB::table('contents')->whereNull('locale')->orWhere('locale', '')->update(['locale' => 'en']);
    }
};
