<?php

namespace App\Mcp\OAuth;

use App\Mcp\Exceptions\McpOAuthException;
use App\Models\McpOAuthClient;
use App\Models\McpOAuthCode;
use App\Models\McpOAuthToken;
use App\Models\User;
use App\Workflow\Support\SafeHttpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class McpOAuthService
{
    public function issuer(): string
    {
        return rtrim((string) config('app.url', 'http://localhost'), '/');
    }

    public function resourceUrl(): string
    {
        $path = ltrim((string) config('mcp.path', 'mcp'), '/');

        return $this->issuer() . '/' . $path;
    }

    public function protectedResourceMetadata(): array
    {
        return [
            'resource' => $this->resourceUrl(),
            'authorization_servers' => [$this->issuer()],
            'scopes_supported' => (array) config('mcp.scopes', ['mcp:read', 'mcp:write']),
            'bearer_methods_supported' => ['header'],
        ];
    }

    public function authorizationServerMetadata(): array
    {
        $issuer = $this->issuer();

        return [
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer . '/oauth/mcp/authorize',
            'token_endpoint' => $issuer . '/oauth/mcp/token',
            'registration_endpoint' => $issuer . '/oauth/mcp/register',
            'revocation_endpoint' => $issuer . '/oauth/mcp/revoke',
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => (array) config('mcp.scopes', ['mcp:read', 'mcp:write']),
            'client_id_metadata_document_supported' => true,
        ];
    }

    public function registerClient(array $payload): array
    {
        $rawUris = $payload['redirect_uris'] ?? null;

        if (! is_array($rawUris) || $rawUris === [] || count($rawUris) > 10) {
            throw McpOAuthException::make('invalid_redirect_uri', 'At least one valid redirect_uri is required.');
        }

        $redirectUris = [];

        foreach ($rawUris as $uri) {
            if (! is_string($uri)) {
                throw McpOAuthException::make('invalid_redirect_uri', 'Redirect URI must be a string.');
            }

            $cleanUri = trim($uri);

            if (! $this->isValidRedirectUri($cleanUri)) {
                throw McpOAuthException::make('invalid_redirect_uri', "Unsupported redirect_uri: $cleanUri");
            }

            $redirectUris[] = $cleanUri;
        }

        $authMethod = (string) ($payload['token_endpoint_auth_method'] ?? 'none');

        if (! in_array($authMethod, ['none', 'client_secret_post', 'client_secret_basic'], true)) {
            $authMethod = 'none';
        }

        $rawGrants = is_array($payload['grant_types'] ?? null)
            ? $payload['grant_types']
            : ['authorization_code', 'refresh_token'];

        $grantTypes = array_values(array_intersect($rawGrants, ['authorization_code', 'refresh_token']));

        if ($grantTypes === []) {
            $grantTypes = ['authorization_code', 'refresh_token'];
        }

        $clientName = trim(strip_tags((string) ($payload['client_name'] ?? 'MCP Client')));
        $clientName = $clientName !== '' ? mb_substr($clientName, 0, 150) : 'MCP Client';

        $clientId = 'tmk_' . Str::random(32);
        $plainSecret = $authMethod !== 'none' ? 'tmks_' . Str::random(48) : null;

        $client = McpOAuthClient::query()->create([
            'client_id' => $clientId,
            'client_secret_hash' => $plainSecret !== null ? Hash::make($plainSecret) : null,
            'name' => $clientName,
            'redirect_uris' => array_values(array_unique($redirectUris)),
            'grant_types' => $grantTypes,
            'token_endpoint_auth_method' => $authMethod,
            'is_cimd' => false,
        ]);

        $response = [
            'client_id' => $client->client_id,
            'client_name' => $client->name,
            'redirect_uris' => $client->redirect_uris,
            'grant_types' => $client->grant_types,
            'response_types' => ['code'],
            'token_endpoint_auth_method' => $client->token_endpoint_auth_method,
            'client_id_issued_at' => $client->created_at?->timestamp ?? time(),
        ];

        if ($plainSecret !== null) {
            $response['client_secret'] = $plainSecret;
            $response['client_secret_expires_at'] = 0;
        }

        return $response;
    }

    public function resolveClient(string $clientId): McpOAuthClient
    {
        $clientId = trim($clientId);

        if ($clientId === '' || mb_strlen($clientId) > 255) {
            throw McpOAuthException::make('invalid_client', 'Client ID is missing or invalid.', 400);
        }

        $existing = McpOAuthClient::query()->where('client_id', $clientId)->first();

        if ($existing && ! $existing->is_cimd) {
            return $existing;
        }

        if (str_starts_with($clientId, 'https://')) {
            return $this->resolveCimdClient($clientId, $existing);
        }

        throw McpOAuthException::make('invalid_client', 'Unknown OAuth client.', 400);
    }

    public function normalizeScopes(?string $rawScope): array
    {
        $supported = (array) config('mcp.scopes', ['mcp:read', 'mcp:write']);
        $raw = trim((string) $rawScope);

        if ($raw === '') {
            return $supported;
        }

        $requested = preg_split('/\s+/', $raw) ?: [];
        $valid = array_values(array_intersect($requested, $supported));

        return $valid !== [] ? $valid : $supported;
    }

    public function validateResource(?string $resource): ?string
    {
        $clean = trim((string) $resource);

        if ($clean === '') {
            return $this->resourceUrl();
        }

        $normalized = rtrim($clean, '/');
        $allowed = [
            rtrim($this->resourceUrl(), '/'),
            rtrim($this->issuer(), '/'),
        ];

        if (! in_array($normalized, $allowed, true)) {
            throw McpOAuthException::make('invalid_target', 'The requested resource parameter is invalid.');
        }

        return $this->resourceUrl();
    }

    public function issueAuthorizationCode(
        User $user,
        McpOAuthClient $client,
        string $redirectUri,
        string $codeChallenge,
        string $codeChallengeMethod,
        array $scopes,
        ?string $resource,
    ): string {
        $plainCode = 'tmkc_' . Str::random(48);
        $ttl = max(60, (int) config('mcp.oauth.auth_code_ttl_seconds', 300));

        McpOAuthCode::query()->create([
            'code_hash' => hash('sha256', $plainCode),
            'client_id' => $client->client_id,
            'user_id' => $user->id,
            'redirect_uri' => $redirectUri,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'scopes' => $scopes,
            'resource' => $resource ?: $this->resourceUrl(),
            'expires_at' => now()->addSeconds($ttl),
        ]);

        return $plainCode;
    }

    public function exchangeAuthorizationCode(
        string $code,
        string $clientId,
        ?string $clientSecret,
        string $redirectUri,
        string $codeVerifier,
        ?string $resource,
    ): array {
        if ($code === '' || $codeVerifier === '' || $redirectUri === '') {
            throw McpOAuthException::make('invalid_request', 'Missing required token exchange parameters.');
        }

        $client = $this->resolveClient($clientId);
        $this->assertClientSecret($client, $clientSecret);
        $validatedResource = $this->validateResource($resource);

        return DB::transaction(function () use ($code, $client, $redirectUri, $codeVerifier, $validatedResource): array {
            $record = McpOAuthCode::query()
                ->where('code_hash', hash('sha256', $code))
                ->lockForUpdate()
                ->first();

            if (! $record || ! $record->isValid()) {
                throw McpOAuthException::make('invalid_grant', 'Authorization code is invalid or expired.');
            }

            if ($record->client_id !== $client->client_id || $record->redirect_uri !== $redirectUri) {
                throw McpOAuthException::make('invalid_grant', 'Authorization code does not match client or redirect_uri.');
            }

            if (! $this->verifyPkce($record->code_challenge, $record->code_challenge_method, $codeVerifier)) {
                throw McpOAuthException::make('invalid_grant', 'PKCE code_verifier verification failed.');
            }

            $record->update(['used_at' => now()]);

            $user = User::query()->find($record->user_id);

            if (! $user) {
                throw McpOAuthException::make('invalid_grant', 'User account no longer exists.');
            }

            return $this->issueTokenPair(
                user: $user,
                clientId: $client->client_id,
                scopes: is_array($record->scopes) ? $record->scopes : ['mcp:read', 'mcp:write'],
                resource: $validatedResource ?: ($record->resource ?: $this->resourceUrl()),
            );
        });
    }

    public function refreshAccessToken(
        string $refreshToken,
        string $clientId,
        ?string $clientSecret,
        ?string $resource,
    ): array {
        if ($refreshToken === '') {
            throw McpOAuthException::make('invalid_request', 'Missing refresh_token.');
        }

        $client = $this->resolveClient($clientId);
        $this->assertClientSecret($client, $clientSecret);
        $validatedResource = $this->validateResource($resource);

        return DB::transaction(function () use ($refreshToken, $client, $validatedResource): array {
            $token = McpOAuthToken::query()
                ->where('refresh_token_hash', hash('sha256', $refreshToken))
                ->lockForUpdate()
                ->first();

            if (! $token || ! $token->isRefreshValid()) {
                throw McpOAuthException::make('invalid_grant', 'Refresh token is invalid or expired.');
            }

            if ($token->client_id !== $client->client_id) {
                throw McpOAuthException::make('invalid_grant', 'Refresh token does not belong to this client.');
            }

            $user = User::query()->find($token->user_id);

            if (! $user) {
                throw McpOAuthException::make('invalid_grant', 'User account no longer exists.');
            }

            $token->update(['revoked_at' => now()]);

            return $this->issueTokenPair(
                user: $user,
                clientId: $client->client_id,
                scopes: is_array($token->scopes) ? $token->scopes : ['mcp:read', 'mcp:write'],
                resource: $validatedResource ?: ($token->resource ?: $this->resourceUrl()),
            );
        });
    }

    public function revokeToken(string $plainToken): void
    {
        $clean = trim($plainToken);

        if ($clean === '') {
            return;
        }

        $hash = hash('sha256', $clean);

        McpOAuthToken::query()
            ->where(function ($query) use ($hash) {
                $query->where('access_token_hash', $hash)
                    ->orWhere('refresh_token_hash', $hash);
            })
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function authenticateAccessToken(string $plainToken): ?McpOAuthToken
    {
        $clean = trim($plainToken);

        if ($clean === '') {
            return null;
        }

        $token = McpOAuthToken::query()
            ->with('user')
            ->where('access_token_hash', hash('sha256', $clean))
            ->first();

        if (! $token || ! $token->isAccessValid() || ! $token->user) {
            return null;
        }

        return $token;
    }

    public function isValidRedirectUri(string $uri): bool
    {
        if ($uri === '' || mb_strlen($uri) > 500 || str_contains($uri, '#')) {
            return false;
        }

        if (! filter_var($uri, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($uri);

        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return false;
        }

        $scheme = strtolower($parsed['scheme']);
        $host = strtolower($parsed['host']);
        $isLoopback = in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);

        if ($scheme === 'http') {
            return $isLoopback;
        }

        return $scheme === 'https';
    }

    public function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, Accept, Mcp-Session-Id, MCP-Protocol-Version',
            'Access-Control-Expose-Headers' => 'Mcp-Session-Id, WWW-Authenticate',
            'Access-Control-Max-Age' => '86400',
        ];
    }

    private function issueTokenPair(User $user, string $clientId, array $scopes, string $resource): array
    {
        $accessTtl = max(300, (int) config('mcp.oauth.access_token_ttl_seconds', 3600));
        $refreshTtl = max(3600, (int) config('mcp.oauth.refresh_token_ttl_seconds', 2592000));

        $plainAccess = 'tmk_at_' . Str::random(56);
        $plainRefresh = 'tmk_rt_' . Str::random(56);

        McpOAuthToken::query()->create([
            'user_id' => $user->id,
            'client_id' => $clientId,
            'access_token_hash' => hash('sha256', $plainAccess),
            'refresh_token_hash' => hash('sha256', $plainRefresh),
            'scopes' => array_values($scopes),
            'resource' => $resource,
            'access_expires_at' => now()->addSeconds($accessTtl),
            'refresh_expires_at' => now()->addSeconds($refreshTtl),
        ]);

        return [
            'access_token' => $plainAccess,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
            'refresh_token' => $plainRefresh,
            'scope' => implode(' ', $scopes),
        ];
    }

    private function verifyPkce(string $challenge, string $method, string $verifier): bool
    {
        if (strtoupper($method) !== 'S256') {
            return false;
        }

        if (strlen($verifier) < 43 || strlen($verifier) > 128) {
            return false;
        }

        $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return hash_equals($challenge, $computed);
    }

    private function assertClientSecret(McpOAuthClient $client, ?string $providedSecret): void
    {
        if ($client->token_endpoint_auth_method === 'none' || $client->client_secret_hash === null) {
            return;
        }

        if ($providedSecret === null || $providedSecret === '' || ! Hash::check($providedSecret, $client->client_secret_hash)) {
            throw McpOAuthException::make('invalid_client', 'Client authentication failed.', 401);
        }
    }

    private function resolveCimdClient(string $clientIdUrl, ?McpOAuthClient $existing): McpOAuthClient
    {
        $parsed = parse_url($clientIdUrl);

        if (
            ! is_array($parsed)
            || strtolower((string) ($parsed['scheme'] ?? '')) !== 'https'
            || empty($parsed['host'])
            || empty($parsed['path'])
            || $parsed['path'] === '/'
        ) {
            throw McpOAuthException::make('invalid_client', 'Invalid client metadata document URL.');
        }

        $cacheKey = 'mcp_cimd_' . hash('sha256', $clientIdUrl);

        if ($existing && Cache::has($cacheKey)) {
            return $existing;
        }

        try {
            (new SafeHttpClient())->assertSafeUrl($clientIdUrl);

            $response = Http::acceptJson()
                ->withOptions([
                    'allow_redirects' => false,
                    'verify' => true,
                ])
                ->timeout(10)
                ->get($clientIdUrl);
        } catch (Throwable) {
            if ($existing) {
                return $existing;
            }

            throw McpOAuthException::make('invalid_client', 'Unable to fetch client metadata document.');
        }

        if ($response->failed()) {
            throw McpOAuthException::make('invalid_client', 'Client metadata document returned an error.');
        }

        $meta = $response->json();

        if (! is_array($meta) || ($meta['client_id'] ?? null) !== $clientIdUrl) {
            throw McpOAuthException::make('invalid_client', 'Client metadata document client_id mismatch.');
        }

        $rawUris = $meta['redirect_uris'] ?? [];

        if (! is_array($rawUris) || $rawUris === []) {
            throw McpOAuthException::make('invalid_client', 'Client metadata document has no redirect_uris.');
        }

        $validUris = [];

        foreach ($rawUris as $uri) {
            if (is_string($uri) && $this->isValidRedirectUri(trim($uri))) {
                $validUris[] = trim($uri);
            }
        }

        if ($validUris === []) {
            throw McpOAuthException::make('invalid_client', 'Client metadata document has no valid redirect_uris.');
        }

        $name = trim(strip_tags((string) ($meta['client_name'] ?? $parsed['host'])));
        $name = $name !== '' ? mb_substr($name, 0, 150) : $parsed['host'];

        $client = McpOAuthClient::query()->updateOrCreate(
            ['client_id' => $clientIdUrl],
            [
                'client_secret_hash' => null,
                'name' => $name,
                'redirect_uris' => array_values(array_unique($validUris)),
                'grant_types' => ['authorization_code', 'refresh_token'],
                'token_endpoint_auth_method' => 'none',
                'is_cimd' => true,
            ]
        );

        Cache::put($cacheKey, true, now()->addMinutes(30));

        return $client;
    }
}
