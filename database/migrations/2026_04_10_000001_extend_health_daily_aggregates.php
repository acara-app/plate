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
        Schema::table('health_daily_aggregates', function (Blueprint $table): void {
            $table->date('local_date')->nullable();
            $table->string('timezone', 50)->nullable();

            $table->string('unit', 20)->nullable();
            $table->string('canonical_unit', 20)->nullable();

            $table->decimal('value_sum_canonical', 14, 4)->nullable();

            $table->string('aggregation_function', 16)->nullable();
            $table->unsignedSmallInteger('aggregation_version')->default(1);
        });

        DB::table('health_daily_aggregates')
            ->whereNull('local_date')
            ->update(['local_date' => DB::raw('date')]);

        Schema::table('health_daily_aggregates', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'date', 'type_identifier']);
        });

        DB::statement('
            CREATE UNIQUE INDEX health_daily_aggregates_local_unique
            ON health_daily_aggregates (user_id, local_date, type_identifier)
        ');

        DB::statement('
            CREATE INDEX health_daily_aggregates_user_type_local
            ON health_daily_aggregates (user_id, type_identifier, local_date DESC)
        ');
    }
};
