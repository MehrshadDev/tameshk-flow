<?php

return [
    'enabled' => (bool) env('MCP_ENABLED', true),

    'base_url' => env('MCP_BASE_URL'),

    'server_name' => (string) env('MCP_SERVER_NAME', 'Tameshk Flow'),

    'server_version' => (string) env('MCP_SERVER_VERSION', '1.0.0'),

    'path' => 'mcp',

    'protocol_versions' => [
        '2026-07-28',
        '2025-11-25',
        '2025-06-18',
        '2025-03-26',
    ],

    'scopes' => [
        'mcp:read',
        'mcp:write',
    ],

    'oauth' => [
        'auth_code_ttl_seconds' => (int) env('MCP_AUTH_CODE_TTL', 300),
        'access_token_ttl_seconds' => (int) env('MCP_ACCESS_TOKEN_TTL', 3600),
        'refresh_token_ttl_seconds' => (int) env('MCP_REFRESH_TOKEN_TTL', 2592000),
        'allowed_redirect_hosts' => array_values(array_unique(array_filter(array_merge(
            [
                'claude.ai',
                'claude.com',
                'chatgpt.com',
                'chat.openai.com',
                'cursor.sh',
                'localhost',
                '127.0.0.1',
            ],
            array_map(
                fn (string $host) => strtolower(trim($host)),
                explode(',', (string) env('MCP_ALLOWED_REDIRECT_HOSTS', ''))
            ),
        )))),
    ],

    'rate_limits' => [
        'mcp_per_minute' => (int) env('MCP_RATE_LIMIT', 120),
        'oauth_per_minute' => (int) env('MCP_OAUTH_RATE_LIMIT', 30),
    ],
];
