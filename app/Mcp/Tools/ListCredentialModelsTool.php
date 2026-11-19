<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\CredentialController;
use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Exceptions\McpToolException;
use App\Models\User;

final class ListCredentialModelsTool implements McpToolContract
{
    public function name(): string
    {
        return 'list_credential_models';
    }

    public function description(): string
    {
        return 'Fetch available AI model identifiers for one of the user AI credentials (gemini, openai, anthropic, deepseek, groq, openrouter) so you can configure valid model values in chat model or Gemini nodes.';
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
                'credential_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the user AI credential from list_user_credentials.',
                ],
            ],
            'required' => ['credential_id'],
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => true,
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $credentialId = isset($arguments['credential_id']) && is_numeric($arguments['credential_id'])
            ? (int) $arguments['credential_id']
            : 0;

        $credential = $user->credentials()->find($credentialId);

        if (! $credential) {
            throw McpToolException::because('اعتبارنامه انتخاب‌شده در حساب کاربری یافت نشد.');
        }

        $response = app(CredentialController::class)->models($credential);

        if ($response->getStatusCode() !== 200) {
            throw McpToolException::because('امکان دریافت لیست مدل‌ها برای این اعتبارنامه وجود ندارد.');
        }

        $payload = $response->getData(true);
        $models = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return [
            'credential_id' => $credential->id,
            'credential_type' => $credential->type,
            'count' => count($models),
            'models' => $models,
        ];
    }
}
