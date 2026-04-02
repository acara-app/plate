<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:subscription_products,id'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
        ];
    }
}
