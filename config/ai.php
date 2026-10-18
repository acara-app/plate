<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the AI providers below should be the
    | default for AI operations when no explicit provider is provided
    | for the operation. This should be any provider defined below.
    |
    */

    'default' => 'gemini',
    'default_for_images' => 'gemini',
    'default_for_audio' => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings' => 'openai',
    'default_for_reranking' => 'cohere',

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Below you may configure caching strategies for AI related operations
    | such as embedding generation. You are free to adjust these values
    | based on your application's available caching stores and needs.
    |
    */

    'default_model_pricing' => ['input' => 0.50, 'output' => 2.00, 'reasoning' => 0.0, 'cache_read' => 0.25],

    'model_catalog' => [
        'gpt-5.6-terra' => [
            'provider' => 'openai', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => [],
            'pricing' => ['input' => 2.00, 'output' => 12.00, 'reasoning' => 0.0, 'cache_read' => 0.20],
        ],
        'gpt-6-astra' => [
            'provider' => 'openai', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => [],
            'pricing' => ['input' => 10.00, 'output' => 50.00, 'reasoning' => 0.0, 'cache_read' => 1.00],
        ],
        'claude-sonnet-5' => [
            'provider' => 'anthropic', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => [],
            'pricing' => ['input' => 2.00, 'output' => 10.00, 'reasoning' => 0.0, 'cache_read' => 0.20, 'cache_write' => 2.50],
        ],
        'claude-fable-5-1' => [
            'provider' => 'anthropic', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => [],
            'pricing' => ['input' => 10.00, 'output' => 50.00, 'reasoning' => 0.0, 'cache_read' => 1.00, 'cache_write' => 12.50],
        ],
        'gemini-3-flash-preview' => [
            'provider' => 'gemini', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => [],
            'pricing' => ['input' => 0.50, 'output' => 3.00, 'reasoning' => 0.0, 'cache_read' => 0.05],
        ],

        'gpt-5-mini' => [
            'provider' => 'openai', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision', 'web_search'], 'options' => [],
            'pricing' => ['input' => 0.15, 'output' => 2.0, 'reasoning' => 2.0, 'cache_read' => 0.075],
        ],
        'gpt-5.4-mini' => [
            'provider' => 'openai', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision', 'web_search'], 'options' => [],
            'pricing' => ['input' => 0.75, 'output' => 4.5, 'reasoning' => 4.5, 'cache_read' => 0.075],
        ],
        'gpt-5-nano' => [
            'provider' => 'openai', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision', 'web_search'], 'options' => [],
            'pricing' => ['input' => 0.1, 'output' => 0.4, 'reasoning' => 0.4, 'cache_read' => 0.05],
        ],
        'gpt-6-luna' => [
            'provider' => 'openai', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision', 'web_search'], 'options' => [],
            'pricing' => ['input' => 0.10, 'output' => 0.50, 'reasoning' => 0.50, 'cache_read' => 0.01],
        ],
        'gpt-6-sol' => [
            'provider' => 'openai', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision', 'web_search'], 'options' => [],
            'pricing' => ['input' => 2.0, 'output' => 10.0, 'reasoning' => 10.0, 'cache_read' => 0.2],
        ],
        'gpt-6.1-sol' => [
            'provider' => 'openai', 'max_output_tokens' => 128000,
            'capabilities' => ['tools', 'structured_output', 'vision', 'web_search'], 'options' => [],
            'pricing' => ['input' => 2.0, 'output' => 10.0, 'reasoning' => 10.0, 'cache_read' => 0.1],
        ],
        'gemini-3.5-flash' => [
            'provider' => 'gemini', 'max_output_tokens' => 65536,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => ['thinking_level' => 'high', 'thinking_summaries' => 'auto'],
            'pricing' => ['input' => 1.50, 'output' => 9.00, 'reasoning' => 9.00, 'cache_read' => 0.15],
        ],
        'gemini-3.6-flash' => [
            'pricing_changes' => ['2027-01-01' => ['input' => 1.50, 'output' => 7.50, 'reasoning' => 7.50, 'cache_read' => 0.15]],
            'provider' => 'gemini', 'max_output_tokens' => 65536,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => [],
            'pricing' => ['input' => 0.75, 'output' => 3.75, 'reasoning' => 3.75, 'cache_read' => 0.075],
        ],
        'gemini-3.8-flash' => [
            'pricing_changes' => ['2027-01-01' => ['input' => 1.50, 'output' => 7.50, 'reasoning' => 7.50, 'cache_read' => 0.15]],
            'provider' => 'gemini', 'max_output_tokens' => 65536,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => [],
            'pricing' => ['input' => 0.75, 'output' => 3.75, 'reasoning' => 3.75, 'cache_read' => 0.075],
        ],
        'gemini-3.1-pro-preview' => [
            'provider' => 'gemini', 'max_output_tokens' => 65536,
            'capabilities' => ['tools', 'structured_output', 'vision'], 'options' => ['thinking_level' => 'high', 'thinking_summaries' => 'auto'],
            'pricing' => ['input' => 2.0, 'output' => 12.0, 'reasoning' => 12.0, 'cache_read' => 0.2],
        ],
    ],

    'agent_profiles' => [
        App\Ai\Agents\AgentRunner::class => [
            'standard' => env('AI_STANDARD_CHAT_MODEL', 'gpt-6-luna'),
            'paid' => env('AI_PAID_CHAT_MODEL', 'gpt-6.1-sol'),
        ],
        App\Ai\Agents\FitnessSpecialist::class => [
            'standard' => env('AI_STANDARD_SPECIALIST_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_CHAT_MODEL', 'gpt-6.1-sol'),
        ],
        App\Ai\Agents\HealthSpecialist::class => [
            'standard' => env('AI_STANDARD_SPECIALIST_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_CHAT_MODEL', 'gpt-6.1-sol'),
        ],
        App\Ai\Agents\NutritionSpecialist::class => [
            'standard' => env('AI_STANDARD_SPECIALIST_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_CHAT_MODEL', 'gpt-6.1-sol'),
        ],
        App\Ai\Agents\MealPlanSpecialist::class => [
            'standard' => env('AI_STANDARD_SPECIALIST_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_CHAT_MODEL', 'gpt-6.1-sol'),
        ],
        App\Ai\Agents\GlucoseSpikeSpecialist::class => [
            'standard' => env('AI_STANDARD_GLUCOSE_MODEL', 'gpt-6-sol'),
            'paid' => env('AI_PAID_CHAT_MODEL', 'gpt-6.1-sol'),
        ],
        App\Ai\Agents\CaffeineGuidanceAgent::class => [
            'standard' => env('AI_STANDARD_TASK_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_TASK_MODEL', 'gemini-3.8-flash'),
        ],
        App\Ai\Agents\GroceryListAgent::class => [
            'standard' => env('AI_STANDARD_TASK_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_TASK_MODEL', 'gemini-3.8-flash'),
        ],
        App\Ai\Agents\MealPlanAgent::class => [
            'standard' => env('AI_STANDARD_TASK_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_TASK_MODEL', 'gemini-3.8-flash'),
        ],
        App\Ai\Agents\SingleMealAgent::class => [
            'standard' => env('AI_STANDARD_TASK_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_TASK_MODEL', 'gemini-3.8-flash'),
        ],
        App\Ai\Agents\SpikePredictorAgent::class => [
            'standard' => env('AI_STANDARD_GLUCOSE_MODEL', 'gpt-6-sol'),
            'paid' => env('AI_PAID_CHAT_MODEL', 'gpt-6.1-sol'),
        ],
        App\Ai\Agents\FoodItemEstimatorAgent::class => [
            'standard' => env('AI_STANDARD_TASK_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_TASK_MODEL', 'gemini-3.8-flash'),
        ],
        App\Ai\Agents\FoodPhotoAnalyzerAgent::class => [
            'standard' => env('AI_STANDARD_PHOTO_MODEL', 'gemini-3.6-flash'),
            'paid' => env('AI_PAID_PHOTO_MODEL', 'gemini-3.8-flash'),
        ],
        App\Ai\Agents\ConversationTitleGeneratorAgent::class => [
            'standard' => env('AI_BACKGROUND_TEXT_MODEL', 'gpt-5-nano'),
        ],
        App\Ai\Agents\ConversationSummarizerAgent::class => [
            'standard' => env('AI_BACKGROUND_TEXT_MODEL', 'gpt-5-nano'),
        ],
        App\Ai\Agents\EnrichAttributeMetadataAgent::class => [
            'standard' => env('AI_BACKGROUND_METADATA_MODEL', 'gemini-3.6-flash'),
        ],
    ],

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Below are each of your AI providers defined for this application. Each
    | represents an AI provider and API key combination which can be used
    | to perform tasks like text, image, and audio creation via agents.
    |
    */

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key' => env('COHERE_API_KEY'),
        ],

        'eleven' => [
            'driver' => 'eleven',
            'key' => env('ELEVENLABS_API_KEY'),
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
        ],

        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
            'url' => env('GROQ_URL', 'https://api.groq.com/openai/v1'),
        ],

        'jina' => [
            'driver' => 'jina',
            'key' => env('JINA_API_KEY'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
        ],

        'openrouter' => [
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
        ],

        'xai' => [
            'driver' => 'xai',
            'key' => env('XAI_API_KEY'),
            'url' => env('XAI_URL', 'https://api.x.ai/v1'),
        ],
    ],

];
