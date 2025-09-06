<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Chat V2 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI Chat V2 feature with simplified UI and improved stability
    |
    */

    'enabled' => env('AI_CHAT_V2_ENABLED', true),

    'default_provider' => env('AI_CHAT_V2_DEFAULT_PROVIDER', 'openwebui'),
    'default_model' => env('AI_CHAT_V2_DEFAULT_MODEL', 'llama3.2:3b'),

    'providers' => [
        'openwebui' => [
            'name' => 'OpenWebUI',
            'enabled' => env('OPENWEBUI_ENABLED', true),
            'base_url' => env('OPENWEBUI_BASE_URL'),
            'api_key' => env('OPENWEBUI_API_KEY'),
            'default_model' => env('OPENWEBUI_DEFAULT_MODEL', 'qwen3:0.6b'),
            'models' => [
                'llama3.2:3b',
                'llama3.1:8b',
                'mistral:7b',
                'phi3:3.8b',
                'gemma2:9b',
                'qwen2.5:7b'
            ],
            'timeout' => env('OPENWEBUI_TIMEOUT', 90),
            'max_tokens' => env('OPENWEBUI_MAX_TOKENS', 1000),
            'temperature' => env('OPENWEBUI_TEMPERATURE', 0.7),
        ],
        'ollama' => [
            'name' => 'Ollama',
            'enabled' => env('OLLAMA_ENABLED', true),
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'default_model' => env('OLLAMA_DEFAULT_MODEL', 'qwen2.5:3b'),
            'models' => [
                'llama3.2:3b',
                'llama3.1:8b',
                'mistral:7b',
                'phi3:3.8b',
                'gemma2:9b',
                'qwen2.5:7b',
                'llama3.2:1b'
            ],
            'timeout' => env('OLLAMA_TIMEOUT', 60),
            'max_tokens' => env('OLLAMA_MAX_TOKENS', 2000),
            'temperature' => env('OLLAMA_TEMPERATURE', 0.7),
        ]
    ],

    'ui' => [
        'simplified_mode' => env('CHAT_V2_SIMPLIFIED_UI', true),
        'auto_scroll' => env('CHAT_V2_AUTO_SCROLL', true),
        'enable_animations' => env('CHAT_V2_ANIMATIONS', true),
        'default_position' => 'bottom-right',
        'default_size' => 'medium',
        'theme' => 'auto',
    ],

    'history' => [
        'default_limit' => env('CHAT_V2_HISTORY_LIMIT', 50),
        'search_depth_days' => env('CHAT_V2_SEARCH_DAYS', 30),
        'max_sessions_per_user' => env('CHAT_V2_MAX_SESSIONS_PER_USER', 20),
    ],

    'defaults' => [
        'provider' => env('AI_CHAT_V2_DEFAULT_PROVIDER', 'openwebui'),
        'model' => env('AI_CHAT_V2_DEFAULT_MODEL', 'llama3.2:3b'),
        'ui' => [
            'theme' => 'auto',
            'position' => 'bottom-right',
            'size' => 'medium',
            'auto_scroll' => true,
            'show_timestamps' => true,
            'enable_sounds' => true,
            'enable_animations' => true
        ],
        'chat' => [
            'auto_save' => true,
            'max_context_messages' => 10,
            'enable_markdown' => true,
            'enable_code_highlighting' => true,
            'typing_indicator' => true
        ],
        'privacy' => [
            'save_history' => true,
            'share_analytics' => false
        ]
    ],

    'security' => [
        'max_message_length' => env('CHAT_V2_MAX_MESSAGE_LENGTH', 5000),
        'rate_limit_per_minute' => env('CHAT_V2_RATE_LIMIT_PER_MINUTE', 10),
    ]
];