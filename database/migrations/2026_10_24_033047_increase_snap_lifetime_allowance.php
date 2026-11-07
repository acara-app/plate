<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('snap_photo_periods')->where('mode', 'trial')->whereNull('ends_at')
            ->where('allowance', 1)->update(['allowance' => 2]);
    }
};
