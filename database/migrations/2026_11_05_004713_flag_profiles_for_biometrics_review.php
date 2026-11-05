<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_profiles')
            ->where('needs_re_onboarding', false)
            ->whereNotNull('weight')
            ->where('height', '>', 0)
            ->where(function (Builder $query): void {
                $query->whereRaw('weight * 10000 >= 45 * height * height')
                    ->orWhereRaw('weight * 10000 < 15 * height * height');
            })
            ->update(['needs_re_onboarding' => true]);
    }
};
