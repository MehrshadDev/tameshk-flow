<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Mcp\Server\McpServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class McpEndpointController extends Controller
{
    public function __construct(
        private readonly McpServer $server,
    ) {}

    public function handle(Request $request): JsonResponse|Response
    {
        $raw = $request->getContent();
        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32700,
                    'message' => 'Parse error: invalid JSON payload.',
                ],
            ], 400);
        }

        $result = $this->server->handle(
            payload: $decoded,
            user: $request->user(),
            token: $request->attributes->get('mcp_token'),
        );

        if ($result === null) {
            return response('', 202);
        }

        return response()->json($result, 200);
    }

    public function stream(): Response
    {
        return response('', 405)->header('Allow', 'POST, DELETE, OPTIONS');
    }

    public function terminate(): Response
    {
        return response('', 204);
    }
}
