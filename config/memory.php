<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Embedding Configuration
    |--------------------------------------------------------------------------
    |
    | Dimensions must match the provider/model configured via config('ai').
    | OpenAI text-embedding-3-small returns 1536-dimensional vectors.
    |
    */

    'embeddings' => [
        'dimensions' => (int) env('MEMORY_EMBEDDING_DIMENSIONS', 1536),
        'timeout' => (int) env('MEMORY_EMBEDDING_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Defaults
    |--------------------------------------------------------------------------
    */

    'search' => [
        'default_limit' => 5,
        'default_min_relevance' => 0.7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrieval (ContextRetriever)
    |--------------------------------------------------------------------------
    |
    | similarity_threshold filters memories below minimum cosine similarity.
    | weights control the composite score: semantic + recency + frequency.
    | max_results caps how many memories are returned after scoring.
    |
    */

    'retrieval' => [
        'similarity_threshold' => (float) env('MEMORY_RETRIEVAL_THRESHOLD', 0.38),
        'max_results' => (int) env('MEMORY_RETRIEVAL_MAX', 7),
        'context_turns' => (int) env('MEMORY_RETRIEVAL_CONTEXT_TURNS', 20),
        'weights' => [
            'semantic' => 0.60,
            'recency' => 0.25,
            'frequency' => 0.15,
        ],
        'recency_half_life_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Extraction (MemoryExtractor)
    |--------------------------------------------------------------------------
    */

    'extraction' => [
        'threshold' => (int) env('MEMORY_EXTRACTION_THRESHOLD', 10),
        'max_memories' => (int) env('MEMORY_EXTRACTION_MAX', 6),
        'max_turns' => (int) env('MEMORY_EXTRACTION_MAX_TURNS', 40),
        'cooldown_minutes' => (int) env('MEMORY_EXTRACTION_COOLDOWN_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Consolidation
    |--------------------------------------------------------------------------
    |
    | max_generation caps how many times a memory line can be re-consolidated
    | to prevent unbounded merging.
    |
    */

    'consolidation' => [
        'max_generation' => 5,
        'similarity_threshold' => (float) env('MEMORY_CONSOLIDATION_THRESHOLD', 0.80),
        'days_lookback' => (int) env('MEMORY_CONSOLIDATION_DAYS_LOOKBACK', 3),
        'max_memories_per_run' => (int) env('MEMORY_CONSOLIDATION_MAX_PER_RUN', 100),
        'max_cluster_size' => 5,
        'min_cluster_size' => 2,
        'jobs_per_minute' => (int) env('MEMORY_CONSOLIDATION_JOBS_PER_MIN', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Decay
    |--------------------------------------------------------------------------
    */

    'decay' => [
        'default_age_days' => 30,
        'default_factor' => 0.9,
        'default_min_importance' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Importance
    |--------------------------------------------------------------------------
    */

    'importance' => [
        'min' => 1,
        'max' => 10,
        'default' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Truths (pinned memories)
    |--------------------------------------------------------------------------
    |
    | max_results caps how many pinned memories render as Core Truths.
    |
    */

    'truths' => [
        'max_results' => (int) env('MEMORY_TRUTHS_MAX', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Agent (provider + model for all Memory\* agents)
    |--------------------------------------------------------------------------
    |
    | Drives provider/model selection for every agent in app/Ai/Agents/Memory/
    | via the UsesMemoryAgentConfig trait:
    | - Categorizer, Reflector, Validator, Extractor, QueryGenerator, MergeDecider.
    |
    | Unset or empty `provider` falls back to "gemini". Unset or empty `model`
    | falls back to the provider's default text model. Timeout is per-agent via
    | #[Timeout] attributes, not this key — keep for ops visibility only.
    |
    */

    'ai_agent' => [
        'provider' => env('MEMORY_AI_PROVIDER'),
        'model' => env('MEMORY_AI_MODEL'),
        'timeout' => (int) env('MEMORY_AI_TIMEOUT', 60),
    ],

];
