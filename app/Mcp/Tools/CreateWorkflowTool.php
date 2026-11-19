<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\WorkflowGraphBuilder;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CreateWorkflowTool implements McpToolContract
{
    public function __construct(
        private readonly WorkflowGraphBuilder $graphBuilder,
    ) {}

    public function name(): string
    {
        return 'create_workflow';
    }

    public function description(): string
    {
        return 'Create a new workflow in Tameshk Flow and optionally populate its complete nodes and edges with clean automatic visual layout in one atomic call.';
    }

    public function requiredScope(): string
    {
        return 'mcp:write';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Workflow display name (max 100 chars).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional short description of what this workflow does (max 500 chars).',
                ],
                'nodes' => [
                    'type' => 'array',
                    'description' => 'Optional array of nodes to create inside the workflow.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'key' => ['type' => 'string', 'description' => 'Unique key referenced by edges (e.g. "trigger", "agent", "reply").'],
                            'type' => ['type' => 'string', 'description' => 'Registered node type from list_node_catalog (or "sticky_note").'],
                            'name' => ['type' => 'string', 'description' => 'Human-readable node label.'],
                            'config' => ['type' => 'object', 'description' => 'Node configuration matching get_node_schema.'],
                            'position' => [
                                'type' => 'object',
                                'properties' => [
                                    'x' => ['type' => 'number'],
                                    'y' => ['type' => 'number'],
                                ],
                            ],
                        ],
                        'required' => ['key', 'type'],
                    ],
                ],
                'edges' => [
                    'type' => 'array',
                    'description' => 'Optional array of connections between node keys.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'source' => ['type' => 'string'],
                            'target' => ['type' => 'string'],
                            'sourceHandle' => ['type' => 'string'],
                            'targetHandle' => ['type' => 'string'],
                        ],
                        'required' => ['source', 'target'],
                    ],
                ],
                'auto_layout' => [
                    'type' => 'boolean',
                    'description' => 'Automatically compute clean, non-overlapping grid-snapped coordinates (default true).',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => false,
            'destructiveHint' => false,
            'idempotentHint' => false,
            'openWorldHint' => false,
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $name = mb_substr(trim(strip_tags((string) ($arguments['name'] ?? ''))), 0, 100);

        if ($name === '') {
            throw McpToolException::because('نام ورکفلو (name) الزامی است.');
        }

        $description = trim(strip_tags((string) ($arguments['description'] ?? '')));
        $description = $description !== '' ? mb_substr($description, 0, 500) : null;

        $nodes = is_array($arguments['nodes'] ?? null) ? $arguments['nodes'] : [];
        $edges = is_array($arguments['edges'] ?? null) ? $arguments['edges'] : [];
        $autoLayout = array_key_exists('auto_layout', $arguments) ? (bool) $arguments['auto_layout'] : true;

        return DB::transaction(function () use ($user, $name, $description, $nodes, $edges, $autoLayout): array {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $authResponse = Gate::forUser($lockedUser)->inspect('create', Workflow::class);

            if ($authResponse->denied()) {
                throw McpToolException::because($authResponse->message() ?: 'سقف تعداد ورکفلوهای مجاز پر شده است.');
            }

            $workflow = $lockedUser->workflows()->create([
                'name' => $name,
                'description' => $description,
                'settings' => ['sort_order' => '000000'],
            ]);

            if ($nodes === []) {
                return [
                    'workflow' => [
                        'id' => $workflow->id,
                        'public_id' => $workflow->public_id,
                        'name' => $workflow->name,
                        'description' => $workflow->description,
                        'is_active' => $workflow->is_active,
                        'is_published' => $workflow->isPublished(),
                        'editor_url' => route('app.workflows.show', $workflow),
                    ],
                    'node_ids' => (object) [],
                    'nodes_count' => 0,
                    'edges_count' => 0,
                    'publish_readiness_warnings' => [],
                ];
            }

            return $this->graphBuilder->saveGraph(
                user: $lockedUser,
                workflow: $workflow,
                rawNodes: $nodes,
                rawEdges: $edges,
                autoLayout: $autoLayout,
            );
        });
    }
}
