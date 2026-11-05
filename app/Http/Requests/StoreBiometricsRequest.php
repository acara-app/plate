<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BloodType;
use App\Enums\MeasurementSystem;
use App\Enums\Sex;
use App\Enums\WeightUnit;
use App\Services\BodyMassIndex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** @codeCoverageIgnore */
final class StoreBiometricsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'measurement_system' => ['nullable', Rule::enum(MeasurementSystem::class)],
            'age' => ['required', 'integer', 'min:13', 'max:120'],
            ...$this->measurementRules(),
            'sex' => ['required', Rule::enum(Sex::class)],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'confirm_measurements' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $weightUnitName = $this->measurementSystem() === MeasurementSystem::Imperial ? 'pounds' : 'kilograms';

        return [
            'age.required' => 'Please provide your age.',
            'age.min' => 'You must be at least 13 years old to use this service.',
            'age.max' => 'Please enter a valid age.',
            'height.required' => 'Please provide your height.',
            'height.min' => 'Please enter a valid height in centimeters.',
            'height.max' => 'Please enter a valid height in centimeters.',
            'height_feet.required' => 'Please provide your height.',
            'height_feet.integer' => 'Please enter your height in whole feet and inches.',
            'height_feet.min' => 'Please enter a valid height in feet and inches.',
            'height_feet.max' => 'Please enter a valid height in feet and inches.',
            'height_inches.min' => 'Inches must be between 0 and 11.',
            'height_inches.lt' => 'Inches must be between 0 and 11.',
            'weight.required' => 'Please provide your current weight.',
            'weight.min' => "Please enter a valid weight in {$weightUnitName}.",
            'weight.max' => "Please enter a valid weight in {$weightUnitName}.",
            'sex.required' => 'Please select your biological sex.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->boolean('confirm_measurements')) {
                return;
            }

            ['height' => $height, 'weight' => $weight] = $this->metricMeasurements();

            if (BodyMassIndex::isPlausible($weight, $height)) {
                return;
            }

            if ($this->measurementSystem() === MeasurementSystem::Metric && BodyMassIndex::isPlausible(WeightUnit::Lb->toKg($weight), $height)) {
                $validator->errors()->add(
                    'weight_in_pounds',
                    __('Did you mean :weight lb?', ['weight' => (string) $this->float('weight')]),
                );

                return;
            }

            $validator->errors()->add('measurements', __('Please double-check your height and weight.'));
        });
    }

    public function measurementSystem(): MeasurementSystem
    {
        $measurementSystem = $this->input('measurement_system');

        if (! is_string($measurementSystem)) {
            return MeasurementSystem::Metric;
        }

        return MeasurementSystem::tryFrom($measurementSystem) ?? MeasurementSystem::Metric;
    }

    /**
     * @return array{height: float, weight: float}
     */
    public function metricMeasurements(): array
    {
        $measurementSystem = $this->measurementSystem();

        $height = $measurementSystem === MeasurementSystem::Imperial
            ? MeasurementSystem::feetAndInchesToCentimeters($this->integer('height_feet'), $this->float('height_inches'))
            : round($this->float('height'), 2);

        return [
            'height' => $height,
            'weight' => $measurementSystem->weightInKilograms($this->float('weight')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        /** @var array<string, mixed> $attributes */
        $attributes = [
            ...$this->safe()->only(['sex', 'blood_type']),
            'age' => $this->integer('age'),
            ...$this->metricMeasurements(),
        ];

        return $attributes;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function measurementRules(): array
    {
        if ($this->measurementSystem() === MeasurementSystem::Imperial) {
            return [
                'height_feet' => ['required', 'integer', 'min:2', 'max:8'],
                'height_inches' => ['nullable', 'numeric', 'min:0', 'lt:12'],
                'weight' => ['required', 'numeric', 'min:45', 'max:1100'],
            ];
        }

        return [
            'height' => ['required', 'numeric', 'min:50', 'max:300'],
            'weight' => ['required', 'numeric', 'min:20', 'max:500'],
        ];
    }
}
