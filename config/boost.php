<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Boost Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for Laravel Boost MCP server.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Application Purpose
    |--------------------------------------------------------------------------
    |
    | A brief description of what this application does. This will be used
    | by AI assistants to better understand the context of your application.
    |
    */
    'purpose' => env('BOOST_PURPOSE', 'A Laravel 11-based livestock management system with advanced features for poultry farming operations, inventory management, and real-time monitoring.'),

    /*
    |--------------------------------------------------------------------------
    | Browser Logs
    |--------------------------------------------------------------------------
    |
    | Configure whether browser logs should be accessible via the browser-logs
    | tool. This is useful for debugging frontend issues.
    |
    */
    'browser_logs' => env('BOOST_BROWSER_LOGS', true),
    'browser_logs_watcher' => env('BOOST_BROWSER_LOGS_WATCHER', true),

    /*
    |--------------------------------------------------------------------------
    | MCP Server Settings
    |--------------------------------------------------------------------------
    |
    | Configuration options for the MCP server itself.
    |
    */
    'mcp' => [
        'timeout' => env('BOOST_MCP_TIMEOUT', 30),
        'host' => env('BOOST_MCP_HOST', '127.0.0.1'),
        'port' => env('BOOST_MCP_PORT', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, provides additional debugging information and tools.
    |
    */
    'debug' => env('BOOST_DEBUG', env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Tool Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific Laravel Boost tools.
    |
    */
    'tools' => [
        'tinker' => env('BOOST_TINKER_ENABLED', true),
        'database_query' => env('BOOST_DATABASE_QUERY_ENABLED', true),
        'artisan' => env('BOOST_ARTISAN_ENABLED', true),
        'browser_logs' => env('BOOST_BROWSER_LOGS_ENABLED', true),
    ],
];