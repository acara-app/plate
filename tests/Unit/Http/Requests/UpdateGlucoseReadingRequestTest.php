<?php

declare(strict_types=1);

use App\Http\Requests\UpdateGlucoseReadingRequest;

it('has correct validation rules', function (): void {
    $request = new UpdateGlucoseReadingRequest();

    expect($request->rules())->toBeArray()
        ->toHaveKeys(['reading_value', 'reading_type', 'measured_at', 'notes']);
});

it('has custom error messages', function (): void {
    $request = new UpdateGlucoseReadingRequest();

    expect($request->messages())->toBeArray()
        ->toHaveKey('reading_value.required');
});
