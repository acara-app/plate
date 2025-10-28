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
            $invoices = $user->invoices()->take(10);
            $billingHistory = collect($invoices)->map(function (Invoice $invoice): array {
                return [
                    'id' => $invoice->id ?? '',
                    'date' => $invoice->date()->toDateString(),
                    'total' => $invoice->total(),
                    'status' => $invoice->status ?? 'unknown',
                    'download_url' => $invoice->hosted_invoice_url ?? '',
                ];
            })->all();
        } catch (Exception) {
            $billingHistory = [];
        }

        return Inertia::render('billing/index', [
            'billingHistory' => $billingHistory,
        ]);
    }
}
