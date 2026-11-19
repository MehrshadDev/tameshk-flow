<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Models\User;
use App\Subscriptions\SubscriptionManager;

final class ListWorkflowsTool implements McpToolContract
{
    public function __construct(
        private readonly SubscriptionManager $subscriptions,
    ) {}

    public function name(): string
    {
        return 'list_workflows';
    }

    public function description(): string
    {
        return 'List the authenticated user workflows with status, node counts, latest run summary, editor links, and account plan limits.';
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
                'search' => [
                    'type' => 'string',
                    'description' => 'Optional search text matching workflow name or description.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['all', 'active', 'inactive'],
                    'description' => 'Optional filter by active status.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                    'description' => 'Maximum number of workflows to return (default 25).',
                ],
            ],
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
        $search = trim((string) ($arguments['search'] ?? ''));
        $status = (string) ($arguments['status'] ?? 'all');
        $limit = max(1, min(50, (int) ($arguments['limit'] ?? 25)));

        $workflows = $user->workflows()
            ->with(['latestRun'])
            ->withCount('nodes')
            ->search($search)
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        $summary = $this->subscriptions->usageSummary($user);

        return [
            'count' => $workflows->count(),
            'account_limits' => [
                'plan_name' => $summary['plan']->name,
                'total_workflows' => $summary['total_workflows'],
                'max_total_workflows' => $summary['max_total_workflows'],
                'active_workflows' => $summary['active_workflows'],
                'max_active_workflows' => $summary['max_active_workflows'],
                'runs_used' => $summary['runs_used'],
                'max_runs' => $summary['max_runs'],
            ],
            'workflows' => $workflows->map(fn ($workflow) => [
                'id' => $workflow->id,
                'public_id' => $workflow->public_id,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'is_active' => $workflow->is_active,
                'is_published' => $workflow->isPublished(),
                'nodes_count' => $workflow->nodes_count,
                'editor_url' => route('app.workflows.show', $workflow),
                'updated_at' => $workflow->updated_at?->toIso8601String(),
                'latest_run' => $workflow->latestRun ? [
                    'id' => $workflow->latestRun->id,
                    'status' => $workflow->latestRun->status,
                    'error' => $workflow->latestRun->error,
                    'started_at' => $workflow->latestRun->started_at?->toIso8601String(),
                ] : null,
            ])->values()->all(),
        ];
    }
}
