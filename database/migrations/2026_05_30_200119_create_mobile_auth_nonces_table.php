<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_auth_nonces', function (Blueprint $table): void {
            $table->id();
            $table->uuid('nonce_id')->unique();
            $table->string('nonce');
            $table->string('device_identifier')->index();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
};
