<?php

declare(strict_types=1);

use App\Services\AiTransparency;

it('explains that photo values are model estimates, not database lookups', function (): void {
    expect(implode(' ', AiTransparency::pipeline()))->toContain('not retrieved from a database')
        ->and(AiTransparency::snapToTrackFaqs()[0]['a'])->toContain('not looked up in a live database');
});
