<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Contracts\Billing\OffersSubscriptions;
use App\Models\SubscriptionProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class StartPhotoSubscriptionController
{
    public function __invoke(Request $request, SubscriptionProduct $product, OffersSubscriptions $offers): RedirectResponse
    {
        abort_unless($offers->available($product) && filled($product->stripe_price_id) && $product->price > 0, 404);
        $request->session()->put('checkout.selected_product', $product->id);
        $request->session()->put('url.intended', route('checkout.subscription', absolute: false));

        return to_route($request->user() === null ? 'register' : 'checkout.subscription');
    }
}
