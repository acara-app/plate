<?php

declare(strict_types=1);

use App\Services\AiTransparency;

it('explains that photo values are model estimates, not database lookups', function (): void {
    expect(implode(' ', AiTransparency::pipeline()))->toContain('not retrieved from a database')
        ->and(AiTransparency::snapToTrackFaqs()[0]['a'])->toContain('not looked up in a live database');
});

it('describes how long photos are kept and when our team may see them', function (): void {
    expect(implode(' ', AiTransparency::photoHandling()))
        ->toContain('up to an hour')
        ->toContain('kept privately with the meal you save')
        ->toContain('Help improve food recognition')
        ->not->toContain('no image is ever saved');
});
