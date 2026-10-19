<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesSelectedDay;
use Illuminate\Foundation\Http\FormRequest;

final class StoreWaterIntakeRequest extends FormRequest
{
    use ResolvesSelectedDay;

    private const int MAX_AMOUNT_ML = 5000;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'date' => $this->selectedDayRules(),
            'amount_ml' => ['required', 'integer', 'between:0,'.self::MAX_AMOUNT_ML],
        ];
    }

    public function amountMl(): int
    {
        return $this->integer('amount_ml');
    }
}
