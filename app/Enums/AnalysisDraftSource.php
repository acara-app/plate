<?php

declare(strict_types=1);

namespace App\Enums;

enum AnalysisDraftSource: string
{
    case PublicSnapToTrack = 'public_snap_to_track';
    case AuthenticatedSnapToTrack = 'authenticated_snap_to_track';
}
