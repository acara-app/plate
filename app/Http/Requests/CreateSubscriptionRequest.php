<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** @codeCoverageIgnore */
final class CreateSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['bail', 'required', 'integer', 'exists:subscription_products,id'],
            'billing_interval' => ['sometimes', 'in:monthly'],
        ];
    }
}
