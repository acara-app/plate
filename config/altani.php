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
    | - turn_scoped_tools: Tool names whose results are only sent for the turn
    |   that produced them; earlier turns replay a short stub instead
    | - retired_tools: Tools that no longer exist but still appear in saved
    |   conversations, mapped to the tool that replaced them; their results
    |   replay as a stub that points to the replacement
    |
    */
    'context' => [
        'history_limit' => 50,
        'recent_summaries' => 3,
        'turn_scoped_tools' => ['LoadSkill'],
        'retired_tools' => ['activate_skill' => 'LoadSkill'],
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
