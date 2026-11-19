<?php

namespace App\Http\Middleware;

use App\Mcp\OAuth\McpOAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpRequest
{
    public function __construct(
        private readonly McpOAuthService $oauth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('mcp.enabled', true)) {
            abort(404);
        }

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders($this->oauth->corsHeaders());
        }

        $bearer = trim((string) $request->bearerToken());

        if ($bearer === '') {
            return $this->unauthorizedResponse(false);
        }

        $token = $this->oauth->authenticateAccessToken($bearer);

        if (! $token) {
            return $this->unauthorizedResponse(true);
        }

        $token->touchLastUsed();
        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('mcp_token', $token);

        $appUrl = rtrim((string) config('app.url', ''), '/');

        if ($appUrl !== '') {
            URL::forceRootUrl($appUrl);
        }

        try {
            $response = $next($request);
        } finally {
            if ($appUrl !== '') {
                URL::forceRootUrl(null);
            }
        }

        foreach ($this->oauth->corsHeaders() as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    private function unauthorizedResponse(bool $invalidToken): Response
    {
        $metadataUrl = $this->oauth->issuer() . '/.well-known/oauth-protected-resource/' . ltrim((string) config('mcp.path', 'mcp'), '/');
        $scopeStr = implode(' ', (array) config('mcp.scopes', ['mcp:read', 'mcp:write']));

        $header = sprintf('Bearer resource_metadata="%s", scope="%s"', $metadataUrl, $scopeStr);

        if ($invalidToken) {
            $header .= ', error="invalid_token", error_description="The access token is invalid or expired."';
        }

        return response()
            ->json([
                'error' => $invalidToken ? 'invalid_token' : 'unauthorized',
                'error_description' => $invalidToken
                    ? 'Access token is invalid or expired.'
                    : 'Authentication is required to access Tameshk Flow MCP.',
            ], 401)
            ->withHeaders(array_merge($this->oauth->corsHeaders(), [
                'WWW-Authenticate' => $header,
            ]));
    }
}
