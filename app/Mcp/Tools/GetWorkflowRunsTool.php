<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Support\WorkflowLookup;
use App\Models\User;

final class GetWorkflowRunsTool implements McpToolContract
{
    public function __construct(
        private readonly WorkflowLookup $lookup,
    ) {}

    public function name(): string
    {
        return 'get_workflow_runs';
    }

    public function description(): string
    {
        return 'Inspect recent execution runs and per-node errors/outputs for a workflow to help diagnose bugs.';
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
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 20,
                    'description' => 'Number of recent runs to fetch (default 5).',
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
        $limit = max(1, min(20, (int) ($arguments['limit'] ?? 5)));

        $runs = $workflow->runs()
            ->with(['nodeRuns' => fn ($q) => $q->with('node:id,name,type')->orderBy('id')])
            ->latest('id')
            ->limit($limit)
            ->get();

        return [
            'workflow_id' => $workflow->id,
            'workflow_name' => $workflow->name,
            'count' => $runs->count(),
            'runs' => $runs->map(fn ($run) => [
                'id' => $run->id,
                'status' => $run->status,
                'trigger_type' => $run->trigger_type,
                'error' => $run->error,
                'duration_seconds' => $run->durationInSeconds(),
                'started_at' => $run->started_at?->toIso8601String(),
                'node_runs' => $run->nodeRuns->map(fn ($nr) => [
                    'node_id' => $nr->node_id,
                    'node_name' => $nr->node?->name,
                    'node_type' => $nr->node?->type,
                    'status' => $nr->status,
                    'duration_ms' => $nr->duration_ms,
                    'error' => $nr->error,
                    'output_data' => $this->lookup->truncatePayload($nr->output_data, 1500),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
