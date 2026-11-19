<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\WorkflowGraphBuilder;
use App\Mcp\Support\WorkflowLookup;
use App\Models\User;

final class SaveWorkflowGraphTool implements McpToolContract
{
    public function __construct(
        private readonly WorkflowLookup $lookup,
        private readonly WorkflowGraphBuilder $graphBuilder,
    ) {}

    public function name(): string
    {
        return 'save_workflow_graph';
    }

    public function description(): string
    {
        return 'Replace and validate the full nodes and edges graph of an existing workflow, optionally updating its name/description and automatically laying out all nodes cleanly.';
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
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional updated workflow name.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional updated workflow description.',
                ],
                'nodes' => [
                    'type' => 'array',
                    'description' => 'Complete array of workflow nodes. Include existing numeric id when preserving an existing node.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'key' => ['type' => 'string'],
                            'id' => ['type' => 'integer'],
                            'type' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'config' => ['type' => 'object'],
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
                    'description' => 'Complete array of connections between node keys.',
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
                    'description' => 'Automatically arrange all nodes cleanly (default true).',
                ],
            ],
            'required' => ['workflow_id', 'nodes', 'edges'],
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

        if (! is_array($arguments['nodes'] ?? null) || ! is_array($arguments['edges'] ?? null)) {
            throw McpToolException::because('پارامترهای nodes و edges الزامی هستند.');
        }

        $metaUpdates = [];

        if (isset($arguments['name']) && is_string($arguments['name'])) {
            $cleanName = mb_substr(trim(strip_tags($arguments['name'])), 0, 100);

            if ($cleanName !== '') {
                $metaUpdates['name'] = $cleanName;
            }
        }

        if (array_key_exists('description', $arguments)) {
            $cleanDesc = trim(strip_tags((string) ($arguments['description'] ?? '')));
            $metaUpdates['description'] = $cleanDesc !== '' ? mb_substr($cleanDesc, 0, 500) : null;
        }

        if ($metaUpdates !== []) {
            $workflow->update($metaUpdates);
        }

        $autoLayout = !array_key_exists('auto_layout', $arguments) || $arguments['auto_layout'];

        return $this->graphBuilder->saveGraph(
            user: $user,
            workflow: $workflow,
            rawNodes: $arguments['nodes'],
            rawEdges: $arguments['edges'],
            autoLayout: $autoLayout,
        );
    }
}
