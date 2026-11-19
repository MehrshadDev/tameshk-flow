<?php

namespace App\Mcp\Support;

use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Layout\WorkflowAutoLayout;
use App\Models\User;
use App\Models\Workflow;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\Enums\HandleType;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Graph\GraphSaver;
use App\Workflow\Graph\NodeConfigValidator;
use App\Workflow\Nodes\Logic\SwitchNode;
use App\Workflow\Nodes\Triggers\ScheduleTriggerNode;
use App\Workflow\Nodes\Triggers\WebhookTriggerNode;
use App\Workflow\Publishing\WorkflowPublisher;
use App\Workflow\Registry\NodeRegistry;

final class WorkflowGraphBuilder
{
    private const MAX_NODES = 250;
    private const MAX_EDGES = 500;

    public function __construct(
        private readonly NodeRegistry $registry,
        private readonly NodeConfigValidator $validator,
        private readonly WorkflowAutoLayout $autoLayout,
        private readonly GraphSaver $saver,
        private readonly WorkflowPublisher $publisher,
    ) {}

    public function saveGraph(
        User $user,
        Workflow $workflow,
        array $rawNodes,
        array $rawEdges,
        bool $autoLayout = true,
    ): array {
        if (count($rawNodes) > self::MAX_NODES) {
            throw McpToolException::because('حداکثر ۲۵۰ نود در هر ورکفلو مجاز است.');
        }

        if (count($rawEdges) > self::MAX_EDGES) {
            throw McpToolException::because('حداکثر ۵۰۰ اتصال در هر ورکفلو مجاز است.');
        }

        $definitions = $this->registry->definitions();
        $userCredentials = $user->credentials()->pluck('type', 'id');

        $errors = [];
        $warnings = [];
        $normalizedNodes = [];
        $nodeMap = [];
        $usedNames = [];
        $triggerCount = 0;

        foreach (array_values($rawNodes) as $index => $raw) {
            if (! is_array($raw)) {
                $errors["nodes.$index"] = 'ساختار نود نامعتبر است.';
                continue;
            }

            $key = trim((string) ($raw['key'] ?? ''));

            if ($key === '' || mb_strlen($key) > 100) {
                $errors["nodes.$index.key"] = 'هر نود باید یک کلید (key) یکتا و معتبر داشته باشد.';
                continue;
            }

            if (isset($nodeMap[$key])) {
                $errors["nodes.$index.key"] = "کلید نود [$key] تکراری است.";
                continue;
            }

            $type = trim((string) ($raw['type'] ?? ''));

            if ($type === 'sticky_note') {
                $name = $this->uniqueName(
                    trim(strip_tags((string) ($raw['name'] ?? 'Sticky Note'))) ?: 'Sticky Note',
                    $usedNames
                );
                $config = is_array($raw['config'] ?? null) ? $raw['config'] : [];
                $content = (string) ($config['content'] ?? '');

                if (mb_strlen($content) > 10000) {
                    $errors["nodes.$index.config.content"] = "یادداشت «{$name}»: متن بیش از حد طولانی است.";
                    continue;
                }

                $node = [
                    'key' => $key,
                    'id' => isset($raw['id']) && is_numeric($raw['id']) ? (int) $raw['id'] : null,
                    'type' => 'sticky_note',
                    'name' => $name,
                    'position' => $this->normalizePosition($raw['position'] ?? null),
                    'config' => ['content' => $content],
                ];

                $normalizedNodes[] = $node;
                $nodeMap[$key] = $node;
                continue;
            }

            $definition = $definitions[$type] ?? null;

            if (! $definition instanceof NodeDefinition) {
                $errors["nodes.$index.type"] = "نوع نود [$type] ناشناخته است. از ابزار list_node_catalog برای مشاهده انواع معتبر استفاده کنید.";
                continue;
            }

            if (str_starts_with($type, 'trigger.')) {
                $triggerCount++;
            }

            $rawName = trim(strip_tags((string) ($raw['name'] ?? '')));
            $name = $this->uniqueName($rawName !== '' ? $rawName : $definition->name, $usedNames);

            $rawConfig = is_array($raw['config'] ?? null) ? $raw['config'] : [];
            $config = $this->mergeDefaults($definition, $rawConfig);
            $config = array_intersect_key($config, $definition->configSchema + ['disabled' => true]);

            if ($type === 'trigger.webhook' && array_key_exists('path', $config)) {
                $config['path'] = WebhookTriggerNode::normalizePath($config['path']);

                if ($pathError = WebhookTriggerNode::pathError($config['path'], $workflow->id)) {
                    $errors["nodes.$index.config.path"] = "نود «{$name}»: $pathError";
                }
            }

            if ($type === 'trigger.schedule') {
                if ($cronError = ScheduleTriggerNode::cronError($config)) {
                    $errors["nodes.$index.config.cron_expression"] = "نود «{$name}»: $cronError";
                }
            }

            $draftErrors = $this->validator->errors($definition, $config, $userCredentials, complete: false);

            foreach ($draftErrors as $field => $message) {
                $errors["nodes.$index.config.$field"] = "نود «{$name}» ← فیلد «{$field}»: $message";
            }

            if (empty($config['disabled'])) {
                $completeErrors = $this->validator->errors($definition, $config, $userCredentials, complete: true);

                foreach ($completeErrors as $field => $message) {
                    $warnings[] = [
                        'node_key' => $key,
                        'node_name' => $name,
                        'field' => $field,
                        'message' => $message,
                    ];
                }
            }

            $node = [
                'key' => $key,
                'id' => isset($raw['id']) && is_numeric($raw['id']) ? (int) $raw['id'] : null,
                'type' => $type,
                'name' => $name,
                'position' => $this->normalizePosition($raw['position'] ?? null),
                'config' => $config,
            ];

            $normalizedNodes[] = $node;
            $nodeMap[$key] = $node;
        }

        if ($triggerCount > 1) {
            $errors['nodes'] = 'هر ورکفلو حداکثر می‌تواند یک نود شروع‌کننده (trigger.*) داشته باشد.';
        }

        $normalizedEdges = [];
        $singleTargetHandles = [HandleType::ChatModel->value, HandleType::Memory->value];
        $usedSingleSlots = [];

        foreach (array_values($rawEdges) as $index => $rawEdge) {
            if (! is_array($rawEdge)) {
                $errors["edges.$index"] = 'ساختار اتصال نامعتبر است.';
                continue;
            }

            $sourceKey = trim((string) ($rawEdge['source'] ?? ''));
            $targetKey = trim((string) ($rawEdge['target'] ?? ''));

            if (! isset($nodeMap[$sourceKey])) {
                $errors["edges.$index.source"] = "نود مبدا [$sourceKey] در لیست نودها وجود ندارد.";
                continue;
            }

            if (! isset($nodeMap[$targetKey])) {
                $errors["edges.$index.target"] = "نود مقصد [$targetKey] در لیست نودها وجود ندارد.";
                continue;
            }

            if ($sourceKey === $targetKey) {
                $errors["edges.$index"] = 'اتصال نود به خودش مجاز نیست.';
                continue;
            }

            $sourceNode = $nodeMap[$sourceKey];
            $targetNode = $nodeMap[$targetKey];
            $sourceDef = $definitions[$sourceNode['type']] ?? null;
            $targetDef = $definitions[$targetNode['type']] ?? null;

            if (! $sourceDef instanceof NodeDefinition || ! $targetDef instanceof NodeDefinition) {
                $errors["edges.$index"] = 'یادداشت‌ها نمی‌توانند به نودها متصل شوند.';
                continue;
            }

            $sourceHandle = isset($rawEdge['sourceHandle']) && is_string($rawEdge['sourceHandle']) && trim($rawEdge['sourceHandle']) !== ''
                ? trim($rawEdge['sourceHandle'])
                : null;
            $targetHandle = isset($rawEdge['targetHandle']) && is_string($rawEdge['targetHandle']) && trim($rawEdge['targetHandle']) !== ''
                ? trim($rawEdge['targetHandle'])
                : null;

            if (! $this->isSupplyDefinition($sourceDef) && $this->isSupplyDefinition($targetDef)) {
                [$sourceKey, $targetKey] = [$targetKey, $sourceKey];
                [$sourceNode, $targetNode] = [$targetNode, $sourceNode];
                [$sourceDef, $targetDef] = [$targetDef, $sourceDef];
                $inferredSpecial = $sourceDef->outputs[0] ?? HandleType::Tool->value;
                $sourceHandle = $inferredSpecial;
                $targetHandle = $inferredSpecial;
            }

            if ($sourceHandle === null) {
                $sourceHandle = $sourceDef->outputs[0] ?? HandleType::Main->value;
            }

            if ($targetHandle === null) {
                $targetHandle = HandleType::isSpecial($sourceHandle)
                    ? $sourceHandle
                    : ($targetDef->inputs[0] ?? HandleType::Main->value);
            }

            $allowedOutputs = $sourceNode['type'] === 'logic.switch'
                ? SwitchNode::allowedOutputsForConfig($sourceNode['config'])
                : $sourceDef->outputs;

            if (! in_array($sourceHandle, $allowedOutputs, true)) {
                $errors["edges.$index.sourceHandle"] = "نود «{$sourceNode['name']}» خروجی [$sourceHandle] ندارد. خروجی‌های مجاز: " . implode(', ', $allowedOutputs);
                continue;
            }

            if (! in_array($targetHandle, $targetDef->inputs, true)) {
                $errors["edges.$index.targetHandle"] = "نود «{$targetNode['name']}» ورودی [$targetHandle] ندارد. ورودی‌های مجاز: " . implode(', ', $targetDef->inputs);
                continue;
            }

            if (
                HandleType::isSpecial($sourceHandle) !== HandleType::isSpecial($targetHandle)
                || (HandleType::isSpecial($sourceHandle) && $sourceHandle !== $targetHandle)
            ) {
                $errors["edges.$index.targetHandle"] = "اتصال خروجی [$sourceHandle] به ورودی [$targetHandle] سازگار نیست.";
                continue;
            }

            if (in_array($targetHandle, $singleTargetHandles, true)) {
                $slotKey = "$targetKey:$targetHandle";

                if (isset($usedSingleSlots[$slotKey])) {
                    $errors["edges.$index.targetHandle"] = "به نود «{$targetNode['name']}» فقط یک ورودی [$targetHandle] می‌توان متصل کرد.";
                    continue;
                }

                $usedSingleSlots[$slotKey] = true;
            }

            $normalizedEdges[] = [
                'source' => $sourceKey,
                'target' => $targetKey,
                'sourceHandle' => $sourceHandle,
                'targetHandle' => $targetHandle,
            ];
        }

        if ($errors !== []) {
            throw McpToolException::because('اعتبارسنجی گراف ورکفلو ناموفق بود.', $errors);
        }

        $needsLayout = $autoLayout || $this->hasMissingPositions($rawNodes);

        if ($needsLayout) {
            $normalizedNodes = $this->autoLayout->layout($normalizedNodes, $normalizedEdges);
        }

        $ids = $this->saver->save($workflow, $normalizedNodes, $normalizedEdges);

        $unpublishedReason = null;

        try {
            $this->publisher->sync($workflow);
        } catch (WorkflowPublishException $e) {
            $this->publisher->unpublish($workflow);
            $unpublishedReason = $e->summary();
        }

        $workflow->refresh();

        return [
            'workflow' => [
                'id' => $workflow->id,
                'public_id' => $workflow->public_id,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'is_active' => $workflow->is_active,
                'is_published' => $workflow->isPublished(),
                'editor_url' => route('app.workflows.show', $workflow),
            ],
            'node_ids' => $ids,
            'nodes_count' => count($normalizedNodes),
            'edges_count' => count($normalizedEdges),
            'unpublished_reason' => $unpublishedReason,
            'publish_readiness_warnings' => $warnings,
        ];
    }

    public function relayoutExisting(Workflow $workflow): array
    {
        $workflow->load(['nodes', 'edges']);

        $nodes = $workflow->nodes->map(fn ($node) => [
            'key' => (string) $node->id,
            'id' => $node->id,
            'type' => $node->type,
            'name' => $node->name,
            'position' => [
                'x' => (float) $node->position_x,
                'y' => (float) $node->position_y,
            ],
            'config' => is_array($node->config) ? $node->config : [],
        ])->values()->all();

        $edges = $workflow->edges->map(fn ($edge) => [
            'source' => (string) $edge->source_node_id,
            'target' => (string) $edge->target_node_id,
            'sourceHandle' => $edge->source_handle ?? HandleType::Main->value,
            'targetHandle' => $edge->target_handle,
        ])->values()->all();

        $laidOut = $this->autoLayout->layout($nodes, $edges);

        $this->saver->save($workflow, $laidOut, $edges);

        return [
            'workflow_id' => $workflow->id,
            'public_id' => $workflow->public_id,
            'nodes_arranged' => count($laidOut),
            'editor_url' => route('app.workflows.show', $workflow),
        ];
    }

    private function mergeDefaults(NodeDefinition $definition, array $provided): array
    {
        $merged = [];

        foreach ($definition->configSchema as $fieldKey => $field) {
            if (array_key_exists('default', $field) && $field['default'] !== null) {
                $merged[$fieldKey] = $field['default'];
            }
        }

        return array_merge($merged, $provided);
    }

    private function isSupplyDefinition(NodeDefinition $definition): bool
    {
        if ($definition->inputs !== [] || $definition->outputs === []) {
            return false;
        }

        foreach ($definition->outputs as $output) {
            if (! HandleType::isSpecial($output)) {
                return false;
            }
        }

        return true;
    }

    private function normalizePosition(mixed $raw): array
    {
        if (is_array($raw) && is_numeric($raw['x'] ?? null) && is_numeric($raw['y'] ?? null)) {
            return [
                'x' => (float) $raw['x'],
                'y' => (float) $raw['y'],
            ];
        }

        return ['x' => 112.0, 'y' => 256.0];
    }

    private function hasMissingPositions(array $rawNodes): bool
    {
        foreach ($rawNodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $pos = $node['position'] ?? null;

            if (! is_array($pos) || ! is_numeric($pos['x'] ?? null) || ! is_numeric($pos['y'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function uniqueName(string $base, array &$usedNames): string
    {
        $clean = mb_substr(trim($base), 0, 90);
        $clean = $clean !== '' ? $clean : 'Node';
        $lower = mb_strtolower($clean);

        if (! isset($usedNames[$lower])) {
            $usedNames[$lower] = true;

            return $clean;
        }

        $counter = 1;

        while (isset($usedNames[mb_strtolower("$clean $counter")])) {
            $counter++;
        }

        $candidate = mb_substr("$clean $counter", 0, 100);
        $usedNames[mb_strtolower($candidate)] = true;

        return $candidate;
    }
}
