<?php

namespace App\Mcp\Tools;

use App\Credentials\Registry\CredentialRegistry;
use App\Mcp\Contracts\McpToolContract;
use App\Models\User;

final class ListUserCredentialsTool implements McpToolContract
{
    public function __construct(
        private readonly CredentialRegistry $credentials,
    ) {}

    public function name(): string
    {
        return 'list_user_credentials';
    }

    public function description(): string
    {
        return 'List the authenticated user credentials (ID, type, name, connection status — never secrets) so you can attach existing credential_id values to workflow nodes.';
    }

    public function requiredScope(): string
    {
        return 'mcp:read';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'description' => 'Optional credential type filter (e.g. telegram, gemini, openai, gmail, google, woocommerce, mysql).',
                ],
            ],
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => false,
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $typeFilter = trim((string) ($arguments['type'] ?? ''));

        $query = $user->credentials()->latest('id');

        if ($typeFilter !== '') {
            $query->where('type', $typeFilter);
        }

        $items = $query->get()->map(fn ($credential) => [
            'id' => $credential->id,
            'type' => $credential->type,
            'name' => $credential->name,
            'is_connected' => !in_array($credential->type, ['gmail', 'google'], true) || !empty($credential->get('refresh_token')),
            'connected_email' => $credential->get('connected_email'),
            'last_used_at' => $credential->last_used_at?->toIso8601String(),
        ])->values()->all();

        $availableTypes = array_map(fn (array $def) => [
            'key' => $def['key'],
            'name' => $def['name'],
            'description' => $def['description'],
        ], $this->credentials->definitionsAsArray());

        return [
            'count' => count($items),
            'credentials' => $items,
            'available_credential_types' => $availableTypes,
            'credentials_page_url' => route('app.credentials.index'),
        ];
    }
}
