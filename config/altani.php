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

    /*
    |--------------------------------------------------------------------------
    | Streaming Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the durable assistant stream ledger.
    |
    | - run_ttl_minutes: How long a stream run (and its persisted chunks) stays
    |   resumable before it is eligible for pruning. The canonical assistant
    |   message always remains in the conversation history.
    | - poll_interval_ms: How often the SSE tail polls the ledger for new chunks.
    | - stall_seconds: How long the tail waits on a still-queued run (no worker)
    |   before giving up so the client fails fast instead of hanging.
    | - max_tail_seconds: Hard wall-clock cap on a single tail connection.
    | - coalesce_text_deltas: Merge consecutive text deltas into one chunk row to cut
    |   write amplification (set false to persist every delta verbatim).
    |
    */
    'stream' => [
        'run_ttl_minutes' => 30,
        'poll_interval_ms' => 400,
        'stall_seconds' => 20,
        'max_tail_seconds' => 150,
        'coalesce_text_deltas' => true,
    ],

];
