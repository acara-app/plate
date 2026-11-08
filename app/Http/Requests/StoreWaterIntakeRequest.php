<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Actions\RecordWaterIntakeAction;
use App\Http\Requests\Concerns\ResolvesSelectedDay;
use Illuminate\Foundation\Http\FormRequest;

final class StoreWaterIntakeRequest extends FormRequest
{
    use ResolvesSelectedDay;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'date' => $this->selectedDayRules(),
            'amount_ml' => ['required', 'integer', 'between:0,'.RecordWaterIntakeAction::MAX_AMOUNT_ML],
        ];
    }

    public function amountMl(): int
    {
        return $this->integer('amount_ml');
    }
}
