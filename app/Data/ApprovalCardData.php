<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class ApprovalCardData extends Data
{
    public function __construct(
        public string $status,
        public string $summary,
        public bool $canApprove,
        public bool $canReject,
        public ?string $error = null,
        public ?string $notice = null,
    ) {}
}
