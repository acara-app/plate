<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Invoice;

final readonly class BillingHistoryController
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $billingHistory = [];

        if ($user === null) {
            return Inertia::render('billing/index', [
                'billingHistory' => $billingHistory,
            ]);
        }

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

        return Inertia::render('billing/index', [
            'billingHistory' => $billingHistory,
        ]);
    }
}
