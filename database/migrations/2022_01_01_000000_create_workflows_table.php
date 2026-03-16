<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', static function (Blueprint $blueprint): void {
            $blueprint->id('id');
            $blueprint->text('class');
            $blueprint->text('arguments')
                ->nullable();
            $blueprint->text('output')
                ->nullable();
            $blueprint->string('status')
                ->default('pending')
                ->index();
            $blueprint->timestamps(6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
