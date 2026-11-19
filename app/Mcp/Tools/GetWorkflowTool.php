<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Support\WorkflowLookup;
use App\Models\User;
use App\Workflow\Publishing\TriggerEndpoints;

final class GetWorkflowTool implements McpToolContract
{
    public function __construct(
        private readonly WorkflowLookup $lookup,
        private readonly TriggerEndpoints $endpoints,
    ) {}

    public function name(): string
    {
        return 'get_workflow';
    }

    public function description(): string
    {
        return 'Get the full graph (nodes, edges, positions, configs), trigger endpoints, and metadata of a specific workflow owned by the user.';
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
                'workflow_id' => [
                    'type' => 'string',
                    'description' => 'Workflow numeric id or public_id.',
                ],
            ],
            'required' => ['workflow_id'],
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
        $workflow = $this->lookup->findForUser($user, $arguments['workflow_id'] ?? null);
        $workflow->load(['nodes', 'edges']);

        return [
            'workflow' => [
                'id' => $workflow->id,
                'public_id' => $workflow->public_id,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'is_active' => $workflow->is_active,
                'is_published' => $workflow->isPublished(),
                'published_at' => $workflow->published_at?->toIso8601String(),
                'editor_url' => route('app.workflows.show', $workflow),
            ],
            'nodes' => $workflow->nodes->map(fn ($node) => [
                'key' => (string) $node->id,
                'id' => $node->id,
                'type' => $node->type,
                'name' => $node->name,
                'position' => [
                    'x' => $node->position_x,
                    'y' => $node->position_y,
                ],
                'config' => $node->config ?? (object) [],
            ])->values()->all(),
            'edges' => $workflow->edges->map(fn ($edge) => [
                'id' => $edge->id,
                'source' => (string) $edge->source_node_id,
                'target' => (string) $edge->target_node_id,
                'sourceHandle' => $edge->source_handle ?? 'main',
                'targetHandle' => $edge->target_handle,
            ])->values()->all(),
            'endpoints' => $this->endpoints->forWorkflow($workflow),
        ];
    }
}
