<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Mcp\Exceptions\McpToolException;
use App\Models\User;
use App\Workflow\Contracts\PublishableTriggerContract;
use App\Workflow\Registry\NodeRegistry;

final class GetNodeSchemaTool implements McpToolContract
{
    public function __construct(
        private readonly NodeRegistry $registry,
    ) {}

    public function name(): string
    {
        return 'get_node_schema';
    }

    public function description(): string
    {
        return 'Retrieve the exact live configSchema, handles, options, depends_on conditions, and presets for one or more node types. Always call this before configuring nodes in create_workflow or save_workflow_graph.';
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
                'node_types' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'minItems' => 1,
                    'maxItems' => 15,
                    'description' => 'Array of node type identifiers (e.g. ["trigger.telegram", "ai.agent", "ai.model.gemini", "action.telegram"]).',
                ],
            ],
            'required' => ['node_types'],
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
        $rawTypes = $arguments['node_types'] ?? [];

        if (! is_array($rawTypes) || $rawTypes === []) {
            throw McpToolException::because('پارامتر node_types باید یک آرایه غیرخالی از نوع نودها باشد.');
        }

        $definitions = $this->registry->definitions();
        $schemas = [];
        $unknown = [];

        foreach (array_slice(array_unique($rawTypes), 0, 15) as $type) {
            if (! is_string($type) || trim($type) === '') {
                continue;
            }

            $cleanType = trim($type);

            if ($cleanType === 'sticky_note') {
                $schemas[$cleanType] = [
                    'type' => 'sticky_note',
                    'name' => 'Sticky Note',
                    'category' => 'note',
                    'inputs' => [],
                    'outputs' => [],
                    'publishable' => false,
                    'configSchema' => [
                        'content' => [
                            'type' => 'textarea',
                            'label' => 'Markdown Content',
                            'required' => false,
                        ],
                    ],
                    'presets' => [],
                ];
                continue;
            }

            $definition = $definitions[$cleanType] ?? null;

            if ($definition === null) {
                $unknown[] = $cleanType;
                continue;
            }

            $schemas[$cleanType] = $definition->toArray() + [
                    'publishable' => $this->registry->make($cleanType) instanceof PublishableTriggerContract,
                ];
        }

        return [
            'schemas' => $schemas,
            'unknown_types' => $unknown,
            'expression_syntax' => [
                'previous_node_field' => '{{ input.field_name }}',
                'specific_node_field' => "{{ \$('Node Name').item.json.field_name }}",
                'trigger_field' => '{{ trigger.field_name }}',
                'agent_connections' => 'Connect chat_model, memory, and tool supply nodes WITH source=supply_node_key, target=agent_node_key, sourceHandle=chat_model|memory|tool, targetHandle=chat_model|memory|tool.',
            ],
        ];
    }
}
