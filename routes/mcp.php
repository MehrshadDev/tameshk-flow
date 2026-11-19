<?php

use App\Http\Controllers\Mcp\McpEndpointController;
use App\Http\Controllers\Mcp\McpOAuthController;
use App\Http\Middleware\AuthenticateMcpRequest;
use Illuminate\Support\Facades\Route;

$mcpPath = ltrim((string) config('mcp.path', 'mcp'), '/');
$mcpRate = (int) config('mcp.rate_limits.mcp_per_minute', 120);
$oauthRate = (int) config('mcp.rate_limits.oauth_per_minute', 30);

Route::get('/.well-known/oauth-protected-resource', [McpOAuthController::class, 'protectedResourceMetadata'])
    ->middleware('throttle:60,1');

Route::get('/.well-known/oauth-protected-resource/' . $mcpPath, [McpOAuthController::class, 'protectedResourceMetadata'])
    ->middleware('throttle:60,1');

Route::get('/.well-known/oauth-authorization-server', [McpOAuthController::class, 'authorizationServerMetadata'])
    ->middleware('throttle:60,1');

Route::options('/.well-known/{any}', [McpOAuthController::class, 'preflight'])
    ->where('any', '.*');

Route::post('/oauth/mcp/register', [McpOAuthController::class, 'register'])
    ->middleware("throttle:$oauthRate,1")
    ->name('mcp.oauth.register');

Route::post('/oauth/mcp/token', [McpOAuthController::class, 'token'])
    ->middleware('throttle:60,1')
    ->name('mcp.oauth.token');

Route::post('/oauth/mcp/revoke', [McpOAuthController::class, 'revoke'])
    ->middleware("throttle:$oauthRate,1")
    ->name('mcp.oauth.revoke');

Route::options('/oauth/mcp/{any}', [McpOAuthController::class, 'preflight'])
    ->where('any', '.*');

Route::middleware(['web', 'auth', "throttle:$oauthRate,1"])->group(function () {
    Route::get('/oauth/mcp/authorize', [McpOAuthController::class, 'authorizeForm'])
        ->name('mcp.oauth.authorize');
    Route::post('/oauth/mcp/authorize', [McpOAuthController::class, 'approve'])
        ->name('mcp.oauth.approve');
});

Route::options('/' . $mcpPath, [McpOAuthController::class, 'preflight']);

Route::middleware([AuthenticateMcpRequest::class])->group(function () use ($mcpPath, $mcpRate) {
    Route::post('/' . $mcpPath, [McpEndpointController::class, 'handle'])
        ->middleware("throttle:$mcpRate,1")
        ->name('mcp.endpoint');

    Route::get('/' . $mcpPath, [McpEndpointController::class, 'stream']);

    Route::delete('/' . $mcpPath, [McpEndpointController::class, 'terminate']);
});
