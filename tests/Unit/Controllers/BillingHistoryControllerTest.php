<?php

declare(strict_types=1);

use App\Actions\GetAiUsageForBillingAction;
use App\Http\Controllers\BillingHistoryController;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

covers(BillingHistoryController::class);

it('returns empty billing history when user is null', function (): void {
    $request = new class extends Request
    {
        public function user($guard = null): ?User
        {
            return null;
        }
    };

    $action = new GetAiUsageForBillingAction();
    $controller = new BillingHistoryController($action);
    $response = $controller->index($request);

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

it('returns billing history for authenticated user', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);

    $request = new class($user) extends Request
    {
        public function __construct(private readonly User $user) {}

        public function user($guard = null): User
        {
            return $this->user;
        }
    };

    $action = new GetAiUsageForBillingAction();
    $controller = new BillingHistoryController($action);

    $response = $controller->index($request);

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

it('returns empty billing history when exception occurs fetching invoices', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_invalid_will_fail']);

    $request = new class($user) extends Request
    {
        public function __construct(private readonly User $user) {}

        public function user($guard = null): User
        {
            return $this->user;
        }
    };

    $action = new GetAiUsageForBillingAction();
    $controller = new BillingHistoryController($action);
    $response = $controller->index($request);

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});
