<?php

declare(strict_types=1);

use App\Enums\ModelName;

covers(ModelName::class);

it('prices every selectable model from its own rate card rather than the fallback', function (ModelName $model): void {
    expect(array_keys(config()->array('plate.model_pricing.models')))->toContain($model->value);
})->with(ModelName::cases());
