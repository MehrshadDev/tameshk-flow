<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Support\WorkflowGraphBuilder;
use App\Mcp\Support\WorkflowLookup;
use App\Models\User;

final class LayoutWorkflowTool implements McpToolContract
{
    public function __construct(
        private readonly WorkflowLookup $lookup,
        private readonly WorkflowGraphBuilder $graphBuilder,
    ) {}

    public function name(): string
    {
        return 'layout_workflow';
    }

    public function description(): string
    {
        return 'Automatically tidy up and rearrange all nodes of an existing workflow cleanly on the canvas without modifying any node configuration or edges.';
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
            ],
            'required' => ['workflow_id'],
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => false,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => false,
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $workflow = $this->lookup->findForUser($user, $arguments['workflow_id'] ?? null);

        return $this->graphBuilder->relayoutExisting($workflow);
    }
}
