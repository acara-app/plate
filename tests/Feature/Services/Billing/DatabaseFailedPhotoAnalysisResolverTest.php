<?php

declare(strict_types=1);

use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;
use App\Models\AnalysisDraft;
use App\Models\User;
use App\Services\Billing\DatabaseFailedPhotoAnalysisResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('returns no recoverable analysis when the optional ledger is unavailable', function (array $tables): void {
    foreach ($tables as $table) {
        Schema::create($table, function (Blueprint $blueprint): void {
            $blueprint->id();
        });
    }

    $context = new PhotoAnalysisContext(null, (string) Str::uuid(), (string) Str::uuid(), 'public_snap_to_track', 'photo');

    expect(resolve(DatabaseFailedPhotoAnalysisResolver::class)->handle($context))->toBeNull();
})->with([
    'neither ledger table exists' => [[]],
    'requests ledger is missing' => [['snap_photo_accounts']],
    'identity ledger is missing' => [['snap_photo_requests']],
]);

it('recovers completed results or releases only the failed logical request reservation', function (array $changes, bool $released): void {
    $this->freezeTime();
    Schema::create('snap_photo_accounts', function (Blueprint $table): void {
        $table->string('subject')->primary();
        $table->string('owner')->nullable();
    });
    Schema::create('snap_photo_requests', function (Blueprint $table): void {
        $table->id();
        $table->string('subject');
        $table->string('request_key', 64);
        $table->string('fingerprint', 64);
        $table->string('guest_id')->nullable();
        $table->string('status');
        $table->uuid('attempt');
        $table->string('source');
        $table->text('result')->nullable();
        $table->timestamp('created_at');
        $table->unique(['subject', 'request_key']);
    });
    DB::table('snap_photo_accounts')->insert(['subject' => 'guest:original', 'owner' => $changes['owner'] ?? null]);
    $requestId = (string) Str::uuid();
    $analysis = FoodAnalysisData::from(AnalysisDraft::factory()->make()->payload);
    $reservation = [
        'subject' => $changes['subject'] ?? 'guest:original',
        'request_key' => $requestId, 'fingerprint' => 'original-photo', 'guest_id' => 'original',
        'status' => $changes['status'] ?? 'pending', 'attempt' => (string) Str::uuid(),
        'source' => 'public_snap_to_track',
        'result' => ($changes['status'] ?? 'pending') === 'succeeded' ? Crypt::encryptString($analysis->toJson()) : null,
        'created_at' => isset($changes['expired']) ? now('UTC')->subDays(2) : now('UTC'),
    ];
    $id = DB::table('snap_photo_requests')->insertGetId($reservation);
    $replacement = DB::table('snap_photo_requests')->insertGetId([
        ...$reservation, 'request_key' => (string) Str::uuid(), 'attempt' => (string) Str::uuid(),
    ]);
    $before = DB::table('snap_photo_requests')->where('id', $replacement)->first();
    $context = new PhotoAnalysisContext(
        isset($changes['user_id']) ? User::factory()->make(['id' => $changes['user_id']]) : null,
        array_key_exists('guest_id', $changes) ? $changes['guest_id'] : 'original',
        $changes['request_id'] ?? $requestId,
        $changes['source'] ?? 'public_snap_to_track',
        $changes['fingerprint'] ?? 'original-photo',
    );

    foreach (range(1, 2) as $delivery) {
        $result = resolve(DatabaseFailedPhotoAnalysisResolver::class)->handle($context);

        expect($result?->toArray())->toBe(($changes['recover'] ?? false) ? $analysis->toArray() : null);
    }

    $actual = DB::table('snap_photo_requests')->where('id', $id)->first();
    expect($actual->status)->toBe($released ? 'failed' : $reservation['status'])
        ->and($actual->result)->toBe($reservation['result'])
        ->and(DB::table('snap_photo_requests')->where('id', $replacement)->first())->toEqual($before);
})->with([
    'guest request' => [[], true],
    'guest request after account registration' => [['owner' => 'user:7', 'subject' => 'user:7'], true],
    'account request' => [['user_id' => 7, 'subject' => 'user:7'], true],
    'account owns original guest request' => [['user_id' => 7, 'owner' => 'user:7'], true],
    'different account' => [['user_id' => 8, 'subject' => 'user:7', 'owner' => 'user:7'], false],
    'unowned guest request' => [['user_id' => 7], false],
    'different guest' => [['guest_id' => 'another'], false],
    'missing identity' => [['guest_id' => null], false],
    'different photo' => [['fingerprint' => 'another-photo'], false],
    'different request' => [['request_id' => 'another-request'], false],
    'different source' => [['source' => 'another-source'], false],
    'completed result' => [['status' => 'succeeded', 'recover' => true], false],
    'completed result belongs to another account' => [['status' => 'succeeded', 'user_id' => 8, 'subject' => 'user:7', 'owner' => 'user:7'], false],
    'completed result belongs to another photo' => [['status' => 'succeeded', 'fingerprint' => 'another-photo'], false],
    'expired completed result' => [['status' => 'succeeded', 'expired' => true], false],
    'already failed request' => [['status' => 'failed'], false],
]);
