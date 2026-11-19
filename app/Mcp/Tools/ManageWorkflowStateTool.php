<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\WorkflowLookup;
use App\Models\User;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Publishing\TriggerEndpoints;
use App\Workflow\Publishing\WorkflowPublisher;

final class ManageWorkflowStateTool implements McpToolContract
{
    public function __construct(
        private readonly WorkflowLookup $lookup,
        private readonly WorkflowPublisher $publisher,
        private readonly TriggerEndpoints $endpoints,
    ) {}

    public function name(): string
    {
        return 'manage_workflow_state';
    }

    public function description(): string
    {
        return 'Publish, unpublish, activate, deactivate, or rename an existing workflow.';
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
                'action' => [
                    'type' => 'string',
                    'enum' => ['publish', 'unpublish', 'activate', 'deactivate', 'rename'],
                    'description' => 'State transition action to perform.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'New workflow name (required only when action is "rename").',
                ],
            ],
            'required' => ['workflow_id', 'action'],
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => false,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => true,
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $workflow = $this->lookup->findForUser($user, $arguments['workflow_id'] ?? null);
        $action = trim((string) ($arguments['action'] ?? ''));

        try {
            match ($action) {
                'publish' => $this->publisher->publish($workflow),
                'unpublish' => $this->publisher->unpublish($workflow),
                'activate' => $this->publisher->setActive($workflow, true),
                'deactivate' => $this->publisher->setActive($workflow, false),
                'rename' => $this->rename($workflow, (string) ($arguments['name'] ?? '')),
                default => throw McpToolException::because("عملیات [$action] معتبر نیست."),
            };
        } catch (WorkflowPublishException $e) {
            throw McpToolException::because($e->summary(), ['errors' => $e->errors()]);
        }

        $workflow->refresh();

        return [
            'action' => $action,
            'workflow' => [
                'id' => $workflow->id,
                'public_id' => $workflow->public_id,
                'name' => $workflow->name,
                'is_active' => $workflow->is_active,
                'is_published' => $workflow->isPublished(),
                'published_at' => $workflow->published_at?->toIso8601String(),
                'editor_url' => route('app.workflows.show', $workflow),
            ],
            'endpoints' => $this->endpoints->forWorkflow($workflow),
        ];
    }

    private function rename($workflow, string $rawName): void
    {
        $clean = mb_substr(trim(strip_tags($rawName)), 0, 100);

        if ($clean === '') {
            throw McpToolException::because('برای تغییر نام، مقدار معتبر name الزامی است.');
        }

        $workflow->update(['name' => $clean]);
    }
}
