<?php

declare(strict_types=1);

use App\Actions\BuildCaffeineGuidanceSpec;
use App\Data\CaffeineGuidanceData;
use Tests\Fixtures\CaffeineGuidanceFixture;

covers(BuildCaffeineGuidanceSpec::class);

it('builds the result cards from the model copy and the assessment', function (): void {
    $guidance = CaffeineGuidanceData::from(CaffeineGuidanceFixture::response([
        'condition_sections' => [
            1 => ['condition' => 'anxiety', 'title' => 'Anxiety note', 'body' => 'Caffeine can worsen anxiety.', 'tone' => 'blue'],
        ],
    ]));
    $limit = CaffeineGuidanceFixture::limit(['conditions' => ['pregnancy', 'anxiety']]);

    $spec = (new BuildCaffeineGuidanceSpec)->handle($guidance, $limit, 'en');

    expect($spec['elements']['root']['children'])->toBe(['verdict', 'gauge', 'drinks', 'timing', 'guidance', 'condition_0', 'condition_1', 'safety'])
        ->and($spec['elements']['timing']['props'])->toMatchArray(['cutoff_label' => '5:00 PM', 'bedtime_label' => '11:00 PM'])
        ->and($spec['elements']['condition_0']['props']['link_label'])->toBe('ACOG guidance')
        ->and($spec['elements']['condition_1']['props']['link_url'])->toBeNull();
});
