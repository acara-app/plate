<?php

declare(strict_types=1);

use App\Services\Memory\EloquentHistoryPuller;

return [

    /*
    |--------------------------------------------------------------------------
    | Conversation History Puller
    |--------------------------------------------------------------------------
    |
    | Plate-core's memory extraction reads pending conversation turns through
    | the App\Contracts\Memory\PullsConversationHistory contract. Plate-core
    | ships a NullConversationHistoryPuller that returns empty results; here
    | we bind the Eloquent-backed implementation that queries History rows.
    |
    | Merged with plate-core's config/memory.php via mergeConfigFrom — keys
    | not set here fall through to the package defaults.
    |
    */

    'history_puller' => EloquentHistoryPuller::class,

];
