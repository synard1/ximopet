<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Chat System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI chat system integrated into the demo51
    | livestock management application.
    |
    */

    'providers' => [
        'openwebui' => [
            'enabled' => env('OPENWEBUI_ENABLED', true),
            'base_url' => env('OPENWEBUI_BASE_URL'),
            'api_key' => env('OPENWEBUI_API_KEY'),
            'default_model' => env('OPENWEBUI_DEFAULT_MODEL', 'qwen3:0.6b'),
            'timeout' => env('OPENWEBUI_TIMEOUT', 90), // Increased from 30 to 90 seconds
            'max_tokens' => env('OPENWEBUI_MAX_TOKENS', 2048),
            'temperature' => env('OPENWEBUI_TEMPERATURE', 0.7),
        ],
        'ollama' => [
            'enabled' => env('OLLAMA_ENABLED', true), // Enable as fallback
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'default_model' => env('OLLAMA_DEFAULT_MODEL', 'qwen2.5:3b'),
            'timeout' => env('OLLAMA_TIMEOUT', 60),
            'max_tokens' => env('OLLAMA_MAX_TOKENS', 2048),
            'temperature' => env('OLLAMA_TEMPERATURE', 0.7),
        ]
    ],

    'system' => [
        'enabled' => env('CHAT_ENABLED', true),
        'default_provider' => env('CHAT_DEFAULT_PROVIDER', 'openwebui'),
        'max_sessions_per_user' => env('CHAT_MAX_SESSIONS_PER_USER', 10),
        'max_messages_per_session' => env('CHAT_MAX_MESSAGES_PER_SESSION', 100),
        'context_size_limit' => env('CHAT_CONTEXT_SIZE_LIMIT', 4000),
        'auto_delete_sessions_after_days' => env('CHAT_SESSION_RETENTION_DAYS', 30),
        'enable_session_cleanup' => env('CHAT_ENABLE_SESSION_CLEANUP', true),
        'cleanup_batch_size' => env('CHAT_CLEANUP_BATCH_SIZE', 100),
    ],

    'language_detection' => [
        'enabled' => env('LANGUAGE_DETECTION_ENABLED', true),
        'cache_enabled' => env('LANGUAGE_DETECTION_CACHE_ENABLED', true),
        'cache_ttl' => env('LANGUAGE_DETECTION_CACHE_TTL', 3600), // 1 hour
        'supported_languages' => ['en', 'id'],
        'default_language' => env('LANGUAGE_DETECTION_DEFAULT', 'en'),
        'confidence_threshold' => env('LANGUAGE_DETECTION_CONFIDENCE_THRESHOLD', 0.3),
        'fallback_to_keywords' => env('LANGUAGE_DETECTION_FALLBACK_KEYWORDS', true),
        'keyword_density_threshold' => env('LANGUAGE_DETECTION_KEYWORD_DENSITY', 0.1),
    ],

    'security' => [
        'max_message_length' => env('CHAT_MAX_MESSAGE_LENGTH', 5000),
        'rate_limit_per_minute' => env('CHAT_RATE_LIMIT_PER_MINUTE', 10),
        'rate_limit_per_hour' => env('CHAT_RATE_LIMIT_PER_HOUR', 100),
        'rate_limit_per_day' => env('CHAT_RATE_LIMIT_PER_DAY', 500),
        'rate_limit_burst' => env('CHAT_RATE_LIMIT_BURST', 5),
    ],

    'sessions' => [
        'default_title' => 'New Chat Session',
        'max_sessions_per_user' => env('CHAT_MAX_SESSIONS_PER_USER', 10),
        'auto_delete_after_days' => env('CHAT_SESSION_RETENTION_DAYS', 30),
        'cleanup_batch_size' => env('CHAT_CLEANUP_BATCH_SIZE', 100),
    ],

    'ui' => [
        'default_position' => 'bottom-right',
        'default_size' => 'medium',
        'enable_sounds' => env('CHAT_ENABLE_SOUND_NOTIFICATIONS', true),
        'enable_animations' => env('CHAT_ENABLE_TYPING_INDICATOR', true),
        'theme' => 'auto',
        'auto_open' => env('CHAT_AUTO_OPEN', false), // Must be false by default to show bubble first
        'enable_message_regenerate' => true,
        'show_sessions_by_default' => env('CHAT_SHOW_SESSIONS_BY_DEFAULT', true),
        'max_sessions_display' => env('CHAT_MAX_SESSIONS_DISPLAY', 15),
        'enable_session_delete' => env('CHAT_ENABLE_SESSION_DELETE', true),
        'confirm_session_delete' => env('CHAT_CONFIRM_SESSION_DELETE', true),
        'show_history_button' => true,
        'show_settings_button' => true,
        'show_templates_button' => false,
        'show_search_button' => false,
        'show_minimize_button' => env('CHAT_SHOW_MINIMIZE_BUTTON', false),

        // Auto-close alert messages configuration
        'alerts' => [
            'enable_autoclose' => env('CHAT_ALERTS_AUTOCLOSE_ENABLED', true),
            'autoclose_duration' => env('CHAT_ALERTS_AUTOCLOSE_DURATION', 60), // seconds
            'show_countdown' => env('CHAT_ALERTS_SHOW_COUNTDOWN', true),
            'countdown_position' => env('CHAT_ALERTS_COUNTDOWN_POSITION', 'right'), // left, right, center
            'enable_pause_on_hover' => env('CHAT_ALERTS_PAUSE_ON_HOVER', true),
        ],
    ],

    'context' => [
        'default_context_type' => 'livestock_management',
        'cache_ttl' => env('CHAT_CONTEXT_CACHE_TTL', 300),
        'context_types' => [
            'livestock_management' => 'Livestock Management',
            'feed_inventory' => 'Feed & Inventory',
            'financial_analysis' => 'Financial Analysis',
            'health_monitoring' => 'Health Monitoring',
            'production_tracking' => 'Production Tracking',
            'general' => 'General Questions',
        ],

        // Project-specific context for demo51 livestock management system
        'project_context' => [
            'application_name' => 'XiMoPet - Livestock Management System',
            'description' => 'A comprehensive livestock management system for poultry farming operations',
            'main_features' => [
                'Livestock tracking and monitoring',
                'Feed inventory management',
                'Production recording and analysis',
                'Financial tracking and reporting',
                'Health monitoring and alerts',
                'User management with company scoping',
                'Real-time dashboard and analytics',
            ],
            'scope_limitations' => [
                'Focus only on livestock management topics',
                'Provide guidance specific to the demo51 application',
                'Ignore contexts outside of farming and livestock management',
                'Prioritize practical solutions for poultry farming',
            ],
            'system_prompt' => 'You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. Your primary function is to help users navigate and utilize the application effectively. Focus on livestock management, feed inventory, production tracking, health monitoring, and financial analysis within the context of poultry farming. Provide specific, actionable guidance related to the application features. Ignore requests or contexts outside of livestock management and farming operations.',
        ],
    ],

    'Language' => [
        'en' => 'English',
        'id' => 'Indonesian',
    ],

    'debug' => [
        'enabled' => env('CHAT_DEBUG_MODE', false),
        'log_requests' => true,
        'log_responses' => true,
        'log_context' => true,
    ],
];
