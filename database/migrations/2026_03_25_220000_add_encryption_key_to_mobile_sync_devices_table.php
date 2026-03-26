<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_sync_devices', function (Blueprint $table): void {
            $table->text('encryption_key')->nullable()->after('device_identifier');
        });
    }
};
