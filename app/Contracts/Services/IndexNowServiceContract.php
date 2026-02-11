<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\DataObjects\IndexNowResultData;

interface IndexNowServiceContract
{
    /**
     * Submit URLs to IndexNow
     *
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): IndexNowResultData;
}
