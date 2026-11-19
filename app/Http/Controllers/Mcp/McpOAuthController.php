<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Mcp\Exceptions\McpOAuthException;
use App\Mcp\OAuth\McpOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Throwable;

class McpOAuthController extends Controller
{
    public function __construct(
        private readonly McpOAuthService $oauth,
    ) {}

    public function protectedResourceMetadata(): JsonResponse
    {
        abort_unless(config('mcp.enabled', true), 404);

        return response()
            ->json($this->oauth->protectedResourceMetadata())
            ->withHeaders($this->oauth->corsHeaders());
    }

    public function authorizationServerMetadata(): JsonResponse
    {
        abort_unless(config('mcp.enabled', true), 404);

        return response()
            ->json($this->oauth->authorizationServerMetadata())
            ->withHeaders($this->oauth->corsHeaders());
    }

    public function register(Request $request): JsonResponse
    {
        abort_unless(config('mcp.enabled', true), 404);

        try {
            $payload = $request->json()->all();

            if ($payload === []) {
                $payload = $request->all();
            }

            $registered = $this->oauth->registerClient($payload);

            return response()
                ->json($registered, 201)
                ->withHeaders($this->oauth->corsHeaders());
        } catch (McpOAuthException $e) {
            return $this->oauthErrorResponse($e->oauthError, $e->errorDescription, $e->statusCode);
        }
    }

    public function authorizeForm(Request $request): View|RedirectResponse
    {
        abort_unless(config('mcp.enabled', true), 404);

        $clientId = trim((string) $request->query('client_id', ''));
        $redirectUri = trim((string) $request->query('redirect_uri', ''));
        $responseType = trim((string) $request->query('response_type', ''));
        $state = trim((string) $request->query('state', ''));
        $codeChallenge = trim((string) $request->query('code_challenge', ''));
        $codeChallengeMethod = strtoupper(trim((string) $request->query('code_challenge_method', '')));
        $rawScope = trim((string) $request->query('scope', ''));
        $rawResource = trim((string) $request->query('resource', ''));

        try {
            $client = $this->oauth->resolveClient($clientId);
        } catch (McpOAuthException $e) {
            abort(400, $e->errorDescription);
        }

        if ($redirectUri === '' || ! $client->allowsRedirectUri($redirectUri)) {
            abort(400, 'آدرس بازگشت (redirect_uri) با تنظیمات کلاینت مطابقت ندارد.');
        }

        if ($responseType !== 'code') {
            return $this->redirectWithError($redirectUri, 'unsupported_response_type', 'Only response_type=code is supported.', $state);
        }

        if ($codeChallenge === '' || $codeChallengeMethod !== 'S256') {
            return $this->redirectWithError($redirectUri, 'invalid_request', 'PKCE S256 code_challenge is required.', $state);
        }

        try {
            $resource = $this->oauth->validateResource($rawResource);
        } catch (McpOAuthException $e) {
            return $this->redirectWithError($redirectUri, $e->oauthError, $e->errorDescription, $state);
        }

        $scopes = $this->oauth->normalizeScopes($rawScope);

        $encryptedPayload = Crypt::encryptString(json_encode([
            'user_id' => $request->user()->id,
            'client_id' => $client->client_id,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'scopes' => $scopes,
            'resource' => $resource,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]));

        $redirectHost = (string) parse_url($redirectUri, PHP_URL_HOST);

        return view('pages.mcp.authorize', [
            'client' => $client,
            'redirectHost' => $redirectHost,
            'scopes' => $scopes,
            'payload' => $encryptedPayload,
            'user' => $request->user(),
        ]);
    }

    public function approve(Request $request): RedirectResponse
    {
        abort_unless(config('mcp.enabled', true), 404);

        $validated = $request->validate([
            'payload' => ['required', 'string'],
            'decision' => ['required', 'string', 'in:approve,deny'],
        ]);

        try {
            $data = json_decode(Crypt::decryptString($validated['payload']), true);
        } catch (Throwable) {
            abort(400, 'درخواست مجوز نامعتبر یا دستکاری شده است.');
        }

        if (
            ! is_array($data)
            || (int) ($data['user_id'] ?? 0) !== (int) $request->user()->id
            || (int) ($data['expires_at'] ?? 0) < now()->timestamp
        ) {
            abort(400, 'مهلت تایید دسترسی به پایان رسیده است. لطفاً دوباره از کلاینت هوش مصنوعی اقدام کنید.');
        }

        $redirectUri = (string) ($data['redirect_uri'] ?? '');
        $state = (string) ($data['state'] ?? '');
        $client = $this->oauth->resolveClient((string) ($data['client_id'] ?? ''));

        if (! $client->allowsRedirectUri($redirectUri)) {
            abort(400, 'آدرس بازگشت نامعتبر است.');
        }

        if ($validated['decision'] !== 'approve') {
            return $this->redirectWithError($redirectUri, 'access_denied', 'کاربر دسترسی را لغو کرد.', $state);
        }

        $code = $this->oauth->issueAuthorizationCode(
            user: $request->user(),
            client: $client,
            redirectUri: $redirectUri,
            codeChallenge: (string) $data['code_challenge'],
            codeChallengeMethod: (string) $data['code_challenge_method'],
            scopes: is_array($data['scopes'] ?? null) ? $data['scopes'] : ['mcp:read', 'mcp:write'],
            resource: isset($data['resource']) ? (string) $data['resource'] : null,
        );

        $query = array_filter([
            'code' => $code,
            'state' => $state !== '' ? $state : null,
            'iss' => $this->oauth->issuer(),
        ], fn ($v) => $v !== null);

        return redirect()->away($this->appendQuery($redirectUri, $query));
    }

    public function token(Request $request): JsonResponse
    {
        abort_unless(config('mcp.enabled', true), 404);

        $grantType = trim((string) $request->input('grant_type', ''));
        [$clientId, $clientSecret] = $this->extractClientCredentials($request);

        try {
            $tokenPayload = match ($grantType) {
                'authorization_code' => $this->oauth->exchangeAuthorizationCode(
                    code: trim((string) $request->input('code', '')),
                    clientId: $clientId,
                    clientSecret: $clientSecret,
                    redirectUri: trim((string) $request->input('redirect_uri', '')),
                    codeVerifier: trim((string) $request->input('code_verifier', '')),
                    resource: $request->filled('resource') ? trim((string) $request->input('resource')) : null,
                ),
                'refresh_token' => $this->oauth->refreshAccessToken(
                    refreshToken: trim((string) $request->input('refresh_token', '')),
                    clientId: $clientId,
                    clientSecret: $clientSecret,
                    resource: $request->filled('resource') ? trim((string) $request->input('resource')) : null,
                ),
                default => throw McpOAuthException::make('unsupported_grant_type', "Grant type [$grantType] is not supported."),
            };

            return response()
                ->json($tokenPayload, 200)
                ->withHeaders(array_merge($this->oauth->corsHeaders(), [
                    'Cache-Control' => 'no-store',
                    'Pragma' => 'no-cache',
                ]));
        } catch (McpOAuthException $e) {
            return $this->oauthErrorResponse($e->oauthError, $e->errorDescription, $e->statusCode);
        }
    }

    public function revoke(Request $request): Response
    {
        abort_unless(config('mcp.enabled', true), 404);

        $this->oauth->revokeToken((string) $request->input('token', ''));

        return response('', 200)->withHeaders($this->oauth->corsHeaders());
    }

    public function preflight(): Response
    {
        return response('', 204)->withHeaders($this->oauth->corsHeaders());
    }

    private function extractClientCredentials(Request $request): array
    {
        $basicUser = $request->getUser();
        $basicPass = $request->getPassword();

        if ($basicUser !== null && $basicUser !== '') {
            return [urldecode($basicUser), $basicPass !== null ? urldecode($basicPass) : null];
        }

        return [
            trim((string) $request->input('client_id', '')),
            $request->filled('client_secret') ? (string) $request->input('client_secret') : null,
        ];
    }

    private function redirectWithError(string $redirectUri, string $error, string $description, string $state): RedirectResponse
    {
        $query = array_filter([
            'error' => $error,
            'error_description' => $description,
            'state' => $state !== '' ? $state : null,
            'iss' => $this->oauth->issuer(),
        ], fn ($v) => $v !== null);

        return redirect()->away($this->appendQuery($redirectUri, $query));
    }

    private function appendQuery(string $url, array $params): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params);
    }

    private function oauthErrorResponse(string $error, string $description, int $status = 400): JsonResponse
    {
        return response()
            ->json([
                'error' => $error,
                'error_description' => $description,
            ], $status)
            ->withHeaders(array_merge($this->oauth->corsHeaders(), [
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
            ]));
    }
}
