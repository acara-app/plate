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
        DB::table('users')
            ->whereNotNull('preferred_language')
            ->whereNull('locale')
            ->update(['locale' => DB::raw('preferred_language')]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('preferred_language');
        });
    }
};
