<?php

declare(strict_types=1);

namespace App\Enums;

enum PhotoAnalysisStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
