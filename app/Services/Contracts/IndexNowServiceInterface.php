<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DataObjects\IndexNowResultData;

interface IndexNowServiceInterface
{
    /**
     * Submit URLs to IndexNow
     *
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): IndexNowResultData;
}
