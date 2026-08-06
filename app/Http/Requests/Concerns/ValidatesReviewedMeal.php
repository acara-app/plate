<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

trait ValidatesReviewedMeal
{
    private const array TOTAL_CAPS = [
        'calories' => 5000,
        'carbs' => 1000,
        'protein' => 500,
        'fat' => 500,
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.name' => ['required', 'string', 'max:100'],
            'items.*.portion' => ['nullable', 'string', 'max:100'],
            'items.*.calories' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'items.*.protein' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'items.*.carbs' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'items.*.fat' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'items.*.provenance' => ['nullable', 'string', 'in:model,reference,user'],
            'measured_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items');

            if (! is_array($items)) {
                return;
            }

            foreach (self::TOTAL_CAPS as $field => $cap) {
                $total = 0.0;

                foreach ($items as $item) {
                    $value = is_array($item) ? ($item[$field] ?? null) : null;
                    $total += is_numeric($value) ? (float) $value : 0.0;
                }

                if ($total > $cap) {
                    $validator->errors()->add(
                        'items',
                        __('The combined :field of all items may not exceed :cap.', ['field' => $field, 'cap' => $cap]),
                    );
                }
            }
        });
    }
}
