<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use App\Data\Billing\PhotoEntitlement;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class PhotoLimitExceeded extends RuntimeException
{
    public function __construct(public readonly PhotoEntitlement $entitlement)
    {
        parent::__construct($entitlement->mode === 'trial'
            ? 'Your free trial scan has been used. Choose a plan to analyze another photo.'
            : 'Your photo allowance is used up. It resets at '.$entitlement->resetsAt.'.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['error' => 'photo_limit_exceeded', 'photoAllowance' => $this->entitlement->toArray()], 402);
    }
}
