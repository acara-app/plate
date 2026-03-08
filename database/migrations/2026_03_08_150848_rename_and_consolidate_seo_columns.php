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
            $table->json('meta_data')->nullable()->after('is_published');
        });

        $contents = DB::table('contents')->get();

        foreach ($contents as $content) {
            $metaData = [
                'seo_title' => $content->meta_title ?? '',
                'seo_description' => $content->meta_description ?? '',
            ];

            DB::table('contents')
                ->where('id', $content->id)
                ->update(['meta_data' => json_encode($metaData)]);
        }

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropColumn(['meta_title', 'meta_description', 'seo_metadata']);
        });
    }
};
