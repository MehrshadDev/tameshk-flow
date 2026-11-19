<?php

namespace App\Mcp\Server;

use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Registry\McpToolRegistry;
use App\Models\McpOAuthToken;
use App\Models\User;
use App\Workflow\Registry\NodeRegistry;
use Throwable;

final class McpServer
{
    public function __construct(
        private readonly McpToolRegistry $tools,
        private readonly NodeRegistry $nodeRegistry,
    ) {}

    public function handle(array $payload, User $user, ?McpOAuthToken $token = null): ?array
    {
        if (array_is_list($payload)) {
            if ($payload === []) {
                return $this->error(null, -32600, 'Invalid Request');
            }

            $responses = [];

            foreach ($payload as $item) {
                if (! is_array($item)) {
                    $responses[] = $this->error(null, -32600, 'Invalid Request');
                    continue;
                }

                $single = $this->dispatchMessage($item, $user, $token);

                if ($single !== null) {
                    $responses[] = $single;
                }
            }

            return $responses !== [] ? $responses : null;
        }

        return $this->dispatchMessage($payload, $user, $token);
    }

    private function dispatchMessage(array $message, User $user, ?McpOAuthToken $token): ?array
    {
        $hasId = array_key_exists('id', $message);
        $id = $hasId ? $message['id'] : null;
        $method = $message['method'] ?? null;
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        if (($message['jsonrpc'] ?? null) !== '2.0' || ! is_string($method) || $method === '') {
            return $this->error($id, -32600, 'Invalid JSON-RPC 2.0 request.');
        }

        if (! $hasId) {
            return null;
        }

        return match ($method) {
            'initialize' => $this->success($id, $this->handleInitialize($params)),
            'ping' => $this->success($id, (object) []),
            'tools/list' => $this->success($id, [
                'tools' => $this->tools->listDefinitions($token),
            ]),
            'tools/call' => $this->handleToolCall($id, $params, $user, $token),
            'resources/list' => $this->success($id, [
                'resources' => $this->listResources(),
            ]),
            'resources/read' => $this->handleResourceRead($id, $params),
            'prompts/list' => $this->success($id, [
                'prompts' => $this->listPrompts(),
            ]),
            'prompts/get' => $this->handlePromptGet($id, $params),
            default => $this->error($id, -32601, "Method not found: $method"),
        };
    }

    private function handleInitialize(array $params): array
    {
        $supported = (array) config('mcp.protocol_versions', ['2025-11-25', '2025-06-18', '2025-03-26']);
        $requested = is_string($params['protocolVersion'] ?? null) ? trim($params['protocolVersion']) : '';
        $negotiated = in_array($requested, $supported, true) ? $requested : ($supported[0] ?? '2025-11-25');

        return [
            'protocolVersion' => $negotiated,
            'capabilities' => [
                'tools' => ['listChanged' => false],
                'resources' => ['subscribe' => false, 'listChanged' => false],
                'prompts' => ['listChanged' => false],
            ],
            'serverInfo' => [
                'name' => (string) config('mcp.server_name', 'Tameshk Flow'),
                'version' => (string) config('mcp.server_version', '1.0.0'),
            ],
            'instructions' => $this->serverInstructions(),
        ];
    }

    private function handleToolCall(mixed $id, array $params, User $user, ?McpOAuthToken $token): array
    {
        $toolName = trim((string) ($params['name'] ?? ''));
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if ($toolName === '' || ! $this->tools->has($toolName)) {
            return $this->error($id, -32602, "Unknown tool: $toolName");
        }

        $tool = $this->tools->resolve($toolName);

        if ($token !== null && ! $token->hasScope($tool->requiredScope())) {
            return $this->toolError($id, "Insufficient OAuth scope. Required scope: {$tool->requiredScope()}");
        }

        try {
            $result = $tool->execute($user, $arguments);

            return $this->success($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => (string) json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                    ],
                ],
                'structuredContent' => $result,
                'isError' => false,
            ]);
        } catch (McpToolException $e) {
            return $this->toolError($id, $e->getMessage(), $e->details);
        } catch (Throwable $e) {
            report($e);

            return $this->toolError(
                $id,
                config('app.debug') ? $e->getMessage() : 'خطای داخلی سرور هنگام اجرای ابزار رخ داد.'
            );
        }
    }

    private function listResources(): array
    {
        return [
            [
                'uri' => 'tameshk://catalog/nodes',
                'name' => 'Tameshk Flow Node Catalog',
                'description' => 'Live JSON catalog of all registered workflow nodes, handles, categories, and presets.',
                'mimeType' => 'application/json',
            ],
            [
                'uri' => 'tameshk://guide/authoring',
                'name' => 'Tameshk Flow Workflow Authoring Guide',
                'description' => 'Rules for building valid, cleanly laid out workflows, expressions, and AI Agent connections.',
                'mimeType' => 'text/plain',
            ],
        ];
    }

    private function handleResourceRead(mixed $id, array $params): array
    {
        $uri = trim((string) ($params['uri'] ?? ''));

        if ($uri === 'tameshk://catalog/nodes') {
            $catalog = array_values(array_map(
                fn ($def) => $def->toArray(),
                $this->nodeRegistry->definitions()
            ));

            return $this->success($id, [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'application/json',
                        'text' => (string) json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ]);
        }

        if ($uri === 'tameshk://guide/authoring') {
            return $this->success($id, [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'text/plain',
                        'text' => $this->serverInstructions(),
                    ],
                ],
            ]);
        }

        return $this->error($id, -32602, "Unknown resource URI: $uri");
    }

    private function listPrompts(): array
    {
        return [
            [
                'name' => 'design-workflow',
                'description' => 'Design and build a complete, cleanly arranged Tameshk Flow workflow from a natural-language goal.',
                'arguments' => [
                    [
                        'name' => 'goal',
                        'description' => 'What the automation or AI bot should do.',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'tidy-and-audit-workflow',
                'description' => 'Inspect an existing workflow, fix configuration/connection issues, and neatly auto-layout its nodes.',
                'arguments' => [
                    [
                        'name' => 'workflow_id',
                        'description' => 'Workflow ID or public_id to audit and tidy.',
                        'required' => true,
                    ],
                ],
            ],
        ];
    }

    private function handlePromptGet(mixed $id, array $params): array
    {
        $name = trim((string) ($params['name'] ?? ''));
        $args = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if ($name === 'design-workflow') {
            $goal = trim((string) ($args['goal'] ?? 'یک ورکفلو کاربردی بساز'));

            return $this->success($id, [
                'description' => 'Build a Tameshk Flow workflow from scratch',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            'type' => 'text',
                            'text' => "می‌خواهم در تمشک فلو یک ورکفلو با این هدف بسازی: {$goal}\nابتدا با list_node_catalog و list_user_credentials نودها و اعتبارنامه‌های موجود را بررسی کن، سپس با get_node_schema اسکیمای دقیق نودهای انتخابی را بگیر و ورکفلو را با چیدمان خودکار (auto_layout=true) بساز و لینک ویرایشگر را به من بده.",
                        ],
                    ],
                ],
            ]);
        }

        if ($name === 'tidy-and-audit-workflow') {
            $workflowId = trim((string) ($args['workflow_id'] ?? ''));

            return $this->success($id, [
                'description' => 'Audit and neatly rearrange a workflow',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            'type' => 'text',
                            'text' => "ورکفلوی {$workflowId} را با get_workflow و get_workflow_runs بررسی کن، خطاها یا تنظیمات ناقص را گزارش بده و در صورت نیاز چیدمان نودها را با layout_workflow مرتب کن.",
                        ],
                    ],
                ],
            ]);
        }

        return $this->error($id, -32602, "Unknown prompt: $name");
    }

    private function serverInstructions(): string
    {
        return implode("\n", [
            'You are connected to Tameshk Flow, a visual workflow automation & AI agent platform.',
            '1. Always call list_node_catalog first to inspect available node types dynamically instead of guessing types.',
            '2. Always call get_node_schema for the chosen node types before calling create_workflow or save_workflow_graph so every field, option, and depends_on condition is exact.',
            '3. Call list_user_credentials (and list_credential_models for AI credentials) to bind existing credential_id and valid model values. Never ask the user to paste secret API keys into chat; if a credential is missing, save the workflow with the remaining fields configured and share credentials_page_url.',
            '4. Keep auto_layout=true when creating or updating graphs so all nodes, branches, and AI Agent supply nodes are arranged cleanly on the 16px grid without overlaps.',
            '5. Each workflow can have at most one trigger.* node. For ai.agent nodes, connect supply nodes with source=supply_key, target=agent_key, and matching sourceHandle/targetHandle (chat_model, memory, tool).',
            '6. Use {{ input.field }}, {{ $(\'Node Name\').item.json.field }}, or {{ trigger.field }} for dynamic expressions.',
            '7. Always return the clickable editor_url after creating or updating a workflow, and only publish or delete workflows when the user explicitly requests it.',
        ]);
    }

    private function success(mixed $id, mixed $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    private function toolError(mixed $id, string $message, array $details = []): array
    {
        $payload = ['error' => $message];

        if ($details !== []) {
            $payload['details'] = $details;
        }

        return $this->success($id, [
            'content' => [
                [
                    'type' => 'text',
                    'text' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                ],
            ],
            'isError' => true,
        ]);
    }

    private function error(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
