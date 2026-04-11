<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\IndexNowResultData;

interface IndexNowServiceContract
{
    /**
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): IndexNowResultData;
}
