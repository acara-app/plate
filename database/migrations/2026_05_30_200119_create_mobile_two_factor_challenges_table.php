<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_two_factor_challenges', function (Blueprint $table): void {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_identifier');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
};
