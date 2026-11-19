<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\WorkflowLookup;
use App\Models\User;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Exceptions\QuotaExceededException;
use App\Workflow\Runner\WorkflowRunner;
use Throwable;

final class RunWorkflowTool implements McpToolContract
{
    public function __construct(
        private readonly WorkflowLookup $lookup,
        private readonly WorkflowRunner $runner,
    ) {}

    public function name(): string
    {
        return 'run_workflow';
    }

    public function description(): string
    {
        return 'Execute a workflow synchronously with optional trigger_data payload to test and debug it, returning the run status and per-node outputs or errors.';
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
                'workflow_id' => [
                    'type' => 'string',
                    'description' => 'Workflow numeric id or public_id.',
                ],
                'trigger_data' => [
                    'type' => 'object',
                    'description' => 'Optional input JSON payload passed to the trigger node (e.g. {"chatInput": "سلام"} for chat trigger).',
                ],
            ],
            'required' => ['workflow_id'],
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => false,
            'destructiveHint' => false,
            'idempotentHint' => false,
            'openWorldHint' => true,
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $workflow = $this->lookup->findForUser($user, $arguments['workflow_id'] ?? null);
        $triggerData = is_array($arguments['trigger_data'] ?? null) ? $arguments['trigger_data'] : [];

        @set_time_limit(180);

        try {
            $run = $this->runner->run($workflow, $triggerData);
        } catch (QuotaExceededException $e) {
            throw McpToolException::because($e->getMessage());
        } catch (NodeExecutionException|Throwable $e) {
            $run = $workflow->runs()->latest('id')->with('nodeRuns.node')->first();

            if (! $run) {
                throw McpToolException::because($e->getMessage());
            }
        }

        $run->loadMissing('nodeRuns.node');

        return [
            'run_id' => $run->id,
            'workflow_id' => $workflow->id,
            'status' => $run->status,
            'error' => $run->error,
            'duration_seconds' => $run->durationInSeconds(),
            'node_runs' => $run->nodeRuns->map(fn ($nr) => [
                'node_id' => $nr->node_id,
                'node_name' => $nr->node?->name,
                'node_type' => $nr->node?->type,
                'status' => $nr->status,
                'duration_ms' => $nr->duration_ms,
                'error' => $nr->error,
                'output_data' => $this->lookup->truncatePayload($nr->output_data),
            ])->values()->all(),
        ];
    }
}
