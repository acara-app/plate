<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snap_guest_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('guests');
            $table->unsignedInteger('scans');
            $table->unsignedInteger('failed_scans');
            $table->json('guests_by_scans');
            $table->unsignedInteger('came_back');
            $table->unsignedInteger('signed_in');
            $table->timestamps();
        });
    }
};
