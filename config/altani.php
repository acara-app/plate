<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Context Settings
    |--------------------------------------------------------------------------
    |
    | Settings that control what context is sent to the LLM.
    |
    | - history_limit: Maximum conversation messages included in context
    | - recent_summaries: Number of past conversation summaries to include
    |
    */
    'context' => [
        'history_limit' => 50,
        'recent_summaries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Summarization Settings
    |--------------------------------------------------------------------------
    |
    | Controls when and how conversation summarization occurs.
    |
    | - threshold: Min unsummarized messages before triggering summarization
    | - buffer: Recent messages never summarized (protected window)
    | - timeout: API timeout for summary generation (seconds)
    |
    */
    'summarization' => [
        'threshold' => 20,
        'buffer' => 25,
        'timeout' => 90,
    ],

];
