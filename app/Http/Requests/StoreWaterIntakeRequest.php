<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class StoreWaterIntakeRequest extends FormRequest
{
    public const int MAX_AMOUNT_ML = 5000;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$this->today()->toDateString()],
            'amount_ml' => ['required', 'integer', 'between:0,'.self::MAX_AMOUNT_ML],
        ];
    }

    public function selectedDay(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('date')->value(), $this->timezone())->startOfDay();
    }

    public function amountMl(): int
    {
        return $this->integer('amount_ml');
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    private function timezone(): string
    {
        /** @var User $user */
        $user = $this->user();

        return $user->resolveTimezone();
    }
}
