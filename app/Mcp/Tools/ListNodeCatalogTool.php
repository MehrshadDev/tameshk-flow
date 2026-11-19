<?php

namespace App\Mcp\Tools;

use App\Mcp\Contracts\McpToolContract;
use App\Models\User;
use App\Workflow\Contracts\PublishableTriggerContract;
use App\Workflow\Registry\NodeRegistry;

final class ListNodeCatalogTool implements McpToolContract
{
    public function __construct(
        private readonly NodeRegistry $registry,
    ) {}

    public function name(): string
    {
        return 'list_node_catalog';
    }

    public function description(): string
    {
        return 'List all currently available Tameshk Flow node types dynamically from the platform registry. Call this before designing a workflow to discover triggers, AI agents, chat models, memories, tools, actions, logic, and transform nodes.';
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
                'category' => [
                    'type' => 'string',
                    'enum' => ['trigger', 'ai', 'action', 'logic', 'transform'],
                    'description' => 'Optional category filter.',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Optional keyword filter matching node type, name, group, or description.',
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
        $categoryFilter = isset($arguments['category']) ? trim((string) $arguments['category']) : '';
        $search = mb_strtolower(trim((string) ($arguments['search'] ?? '')));

        $items = [];

        foreach ($this->registry->definitions() as $definition) {
            if ($categoryFilter !== '' && $definition->category->value !== $categoryFilter) {
                continue;
            }

            $presets = array_map(fn (array $preset) => [
                'key' => $preset['key'] ?? '',
                'name' => $preset['name'] ?? '',
                'config' => $preset['config'] ?? [],
            ], $definition->presets);

            $entry = [
                'type' => $definition->type,
                'name' => $definition->name,
                'category' => $definition->category->value,
                'group' => $definition->group,
                'description' => $definition->description,
                'inputs' => $definition->inputs,
                'outputs' => $definition->outputs,
                'publishable' => $this->registry->make($definition->type) instanceof PublishableTriggerContract,
                'presets' => $presets,
            ];

            if ($search !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $entry['type'],
                    $entry['name'],
                    (string) $entry['group'],
                    $entry['description'],
                ]));

                if (! str_contains($haystack, $search)) {
                    continue;
                }
            }

            $items[] = $entry;
        }

        if ($categoryFilter === '' && ($search === '' || str_contains('sticky_note note markdown', $search))) {
            $items[] = [
                'type' => 'sticky_note',
                'name' => 'Sticky Note',
                'category' => 'note',
                'group' => null,
                'description' => 'Markdown documentation note placed on the workflow canvas.',
                'inputs' => [],
                'outputs' => [],
                'publishable' => false,
                'presets' => [],
            ];
        }

        return [
            'count' => count($items),
            'nodes' => $items,
        ];
    }
}
