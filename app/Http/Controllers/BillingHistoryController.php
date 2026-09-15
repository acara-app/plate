<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetAiUsageForBillingAction;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\ProvidesAiBudget;
use App\Data\Billing\PhotoAnalysisContext;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Invoice;

final readonly class BillingHistoryController
{
    public function __construct(
        private GetAiUsageForBillingAction $getAiUsageForBilling,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $billingHistory = [];
        $aiUsage = null;
        $monthlyBudget = null;

        if ($user !== null) {
            try {
                $invoices = $user->invoices()->take(10); // @codeCoverageIgnore
                $billingHistory = collect($invoices)->map(function (Invoice $invoice): array { // @codeCoverageIgnore
                    return [ // @codeCoverageIgnore
                        'id' => $invoice->id ?? '', // @codeCoverageIgnore
                        'date' => $invoice->date()->toDateString(), // @codeCoverageIgnore
                        'total' => $invoice->total(), // @codeCoverageIgnore
                        'status' => $invoice->status ?? 'unknown', // @codeCoverageIgnore
                        'download_url' => $invoice->hosted_invoice_url ?? '', // @codeCoverageIgnore
                    ]; // @codeCoverageIgnore
                })->all(); // @codeCoverageIgnore
            } catch (Exception) {
                $billingHistory = [];
            }

            $aiUsage = $this->getAiUsageForBilling->handle($user);
            $monthlyBudget = resolve(ProvidesAiBudget::class)->forUser($user)?->toArray();
        }

        return Inertia::render('billing/index', [
            'photoAllowance' => resolve(ManagesPhotoAnalyses::class)->entitlement($user, PhotoAnalysisContext::guestId($request))->toArray(),
            'monthlyBudget' => $monthlyBudget,
            'billingHistory' => $billingHistory,
            'aiUsage' => $aiUsage,
        ]);
    }
}
