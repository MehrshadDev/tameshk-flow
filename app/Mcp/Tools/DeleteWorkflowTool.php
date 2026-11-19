<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Support\WorkflowLookup;
use App\Models\User;
use App\Workflow\Publishing\WorkflowPublisher;

final class DeleteWorkflowTool implements McpToolContract
{
    public function __construct(
        private readonly WorkflowLookup $lookup,
        private readonly WorkflowPublisher $publisher,
    ) {}

    public function name(): string
    {
        return 'delete_workflow';
    }

    public function description(): string
    {
        return 'Unpublish (cleaning up any active webhooks) and delete an existing workflow owned by the user.';
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
                    'description' => 'Workflow numeric id or public_id to delete.',
                ],
            ],
            'required' => ['workflow_id'],
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => false,
            'destructiveHint' => true,
            'idempotentHint' => false,
            'openWorldHint' => false,
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $workflow = $this->lookup->findForUser($user, $arguments['workflow_id'] ?? null);
        $deletedName = $workflow->name;
        $deletedId = $workflow->id;

        if ($workflow->isPublished()) {
            $this->publisher->unpublish($workflow);
        }

        $workflow->delete();

        return [
            'deleted' => true,
            'workflow_id' => $deletedId,
            'name' => $deletedName,
        ];
    }
}
