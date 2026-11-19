<?php

namespace App\Mcp\Layout;

use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\Enums\HandleType;
use App\Workflow\Nodes\Logic\SwitchNode;
use App\Workflow\Registry\NodeRegistry;

final class WorkflowAutoLayout
{
    private const GRID = 16;
    private const START_X = 112;
    private const CENTER_Y = 256;
    private const STANDARD_WIDTH = 72;
    private const STANDARD_HEIGHT = 72;
    private const AGENT_WIDTH = 288;
    private const AGENT_HEIGHT = 72;
    private const SUPPLY_OFFSET_Y = 160;
    private const SUPPLY_CHAT_OFFSET_X = 16;
    private const SUPPLY_MEMORY_OFFSET_X = 112;
    private const SUPPLY_TOOL_OFFSET_X = 208;
    private const SUPPLY_GAP_X = 112;
    private const SUPPLY_FOOTPRINT_HEIGHT = 128;
    private const COLUMN_GAP = 112;
    private const BRANCH_COLUMN_GAP = 160;
    private const ROW_GAP = 48;
    private const LABEL_CLEARANCE_Y = 64;
    private const BRANCH_SPREAD_Y = 144;
    private const NOTE_WIDTH = 256;
    private const NOTE_OFFSET_Y = 208;

    public function __construct(
        private readonly NodeRegistry $registry,
    ) {}

    public function layout(array $nodes, array $edges): array
    {
        if ($nodes === []) {
            return [];
        }

        $definitions = $this->registry->definitions();
        $nodesByKey = [];
        $indexByKey = [];

        foreach (array_values($nodes) as $idx => $node) {
            $key = (string) $node['key'];
            $nodesByKey[$key] = $node;
            $indexByKey[$key] = $idx;
        }

        $noteKeys = [];
        $supplyKeys = [];
        $flowKeys = [];

        foreach ($nodesByKey as $key => $node) {
            $type = (string) ($node['type'] ?? '');

            if ($type === 'sticky_note') {
                $noteKeys[] = $key;
                continue;
            }

            if ($this->isSupplyNode($type, $definitions)) {
                $supplyKeys[] = $key;
                continue;
            }

            $flowKeys[] = $key;
        }

        $supplySet = array_flip($supplyKeys);
        $flowSet = array_flip($flowKeys);

        $suppliesByHost = [];
        $attachedSupplySet = [];
        $flowOut = [];
        $flowIn = [];

        foreach ($flowKeys as $key) {
            $flowOut[$key] = [];
            $flowIn[$key] = [];
        }

        foreach ($edges as $edge) {
            $src = (string) ($edge['source'] ?? '');
            $tgt = (string) ($edge['target'] ?? '');
            $srcHandle = (string) ($edge['sourceHandle'] ?? HandleType::Main->value);
            $tgtHandle = (string) ($edge['targetHandle'] ?? HandleType::Main->value);

            if ($src === $tgt || ! isset($nodesByKey[$src], $nodesByKey[$tgt])) {
                continue;
            }

            if (isset($supplySet[$src]) && isset($flowSet[$tgt])) {
                $slot = HandleType::isSpecial($tgtHandle) ? $tgtHandle : $srcHandle;
                $suppliesByHost[$tgt][$slot][] = $src;
                $attachedSupplySet[$src] = true;
                continue;
            }

            if (isset($flowSet[$src]) && isset($flowSet[$tgt]) && ! HandleType::isSpecial($srcHandle) && ! HandleType::isSpecial($tgtHandle)) {
                $order = $this->handleOrder($nodesByKey[$src], $srcHandle, $definitions);

                $flowOut[$src][] = [
                    'target' => $tgt,
                    'handle' => $srcHandle,
                    'order' => $order,
                ];
                $flowIn[$tgt][] = [
                    'source' => $src,
                    'handle' => $srcHandle,
                    'order' => $order,
                ];
            }
        }

        $layers = $this->assignLayers($flowKeys, $flowOut, $flowIn, $nodesByKey);
        $columns = [];

        foreach ($layers as $key => $layer) {
            $columns[$layer][] = $key;
        }

        ksort($columns);

        $positions = [];
        $columnX = self::START_X;
        $minY = self::CENTER_Y;
        $maxBottomY = self::CENTER_Y + self::STANDARD_HEIGHT + self::LABEL_CLEARANCE_Y;

        foreach ($columns as $layer => $keys) {
            $desiredMap = [];
            $orderMap = [];
            $heights = [];
            $boxHeights = [];

            foreach ($keys as $key) {
                $heights[$key] = $this->nodeFootprintHeight($nodesByKey[$key], $suppliesByHost[$key] ?? [], $definitions);
                $boxHeights[$key] = $this->nodeBoxHeight($nodesByKey[$key], $definitions);

                if ($layer === 0) {
                    $desiredMap[$key] = self::CENTER_Y;
                    $orderMap[$key] = str_starts_with((string) ($nodesByKey[$key]['type'] ?? ''), 'trigger.') ? 0 : 1;
                } else {
                    $target = $this->desiredPosition($key, $flowIn[$key] ?? [], $flowOut, $positions, $nodesByKey, $definitions);
                    $desiredMap[$key] = $target['y'];
                    $orderMap[$key] = $target['order'];
                }
            }

            usort($keys, function (string $a, string $b) use ($desiredMap, $orderMap, $indexByKey): int {
                if ($desiredMap[$a] !== $desiredMap[$b]) {
                    return $desiredMap[$a] <=> $desiredMap[$b];
                }

                if ($orderMap[$a] !== $orderMap[$b]) {
                    return $orderMap[$a] <=> $orderMap[$b];
                }

                return $indexByKey[$a] <=> $indexByKey[$b];
            });

            $placedY = [];

            if (count($keys) === 1) {
                $onlyKey = $keys[0];
                $placedY[$onlyKey] = $this->snap($desiredMap[$onlyKey]);
            } else {
                $cursorY = null;

                foreach ($keys as $key) {
                    $y = $desiredMap[$key];

                    if ($cursorY !== null && $y < $cursorY) {
                        $y = $cursorY;
                    }

                    $placedY[$key] = $y;
                    $cursorY = $y + $heights[$key] + self::ROW_GAP;
                }

                $avgDesired = array_sum(array_map(fn ($k) => $desiredMap[$k] + ($boxHeights[$k] / 2), $keys)) / count($keys);
                $firstKey = $keys[0];
                $lastKey = $keys[count($keys) - 1];
                $actualSpanCenter = (($placedY[$firstKey] + ($boxHeights[$firstKey] / 2)) + ($placedY[$lastKey] + ($boxHeights[$lastKey] / 2))) / 2;
                $shift = $avgDesired - $actualSpanCenter;

                foreach ($keys as $key) {
                    $placedY[$key] = $this->snap($placedY[$key] + $shift);
                }

                $guardBottom = null;

                foreach ($keys as $key) {
                    if ($guardBottom !== null && $placedY[$key] < $guardBottom) {
                        $placedY[$key] = $this->snap($guardBottom);
                    }

                    $guardBottom = $placedY[$key] + $heights[$key] + self::ROW_GAP;
                }
            }

            $snappedX = $this->snap($columnX);

            foreach ($keys as $key) {
                $y = $placedY[$key];
                $positions[$key] = ['x' => $snappedX, 'y' => $y];
                $minY = min($minY, $y);
                $maxBottomY = max($maxBottomY, $y + $heights[$key]);
            }

            $maxColumnWidth = self::STANDARD_WIDTH;
            $hasBranchingNode = false;

            foreach ($keys as $key) {
                $node = $nodesByKey[$key];
                $w = $this->nodeFootprintWidth($node, $suppliesByHost[$key] ?? []);
                $maxColumnWidth = max($maxColumnWidth, $w);

                if ($this->outputCount($node, $definitions) > 1) {
                    $hasBranchingNode = true;
                }
            }

            $gap = $hasBranchingNode ? self::BRANCH_COLUMN_GAP : self::COLUMN_GAP;
            $columnX = $this->snap($snappedX + $maxColumnWidth + $gap);
        }

        foreach ($suppliesByHost as $hostKey => $slots) {
            if (! isset($positions[$hostKey])) {
                continue;
            }

            $hostX = $positions[$hostKey]['x'];
            $hostY = $positions[$hostKey]['y'];
            $supplyY = $this->snap($hostY + self::SUPPLY_OFFSET_Y);

            foreach ($slots[HandleType::ChatModel->value] ?? [] as $idx => $sKey) {
                $positions[$sKey] = [
                    'x' => $this->snap($hostX + self::SUPPLY_CHAT_OFFSET_X + ($idx * self::SUPPLY_GAP_X)),
                    'y' => $supplyY,
                ];
                $maxBottomY = max($maxBottomY, $supplyY + self::SUPPLY_FOOTPRINT_HEIGHT);
            }

            foreach ($slots[HandleType::Memory->value] ?? [] as $idx => $sKey) {
                $positions[$sKey] = [
                    'x' => $this->snap($hostX + self::SUPPLY_MEMORY_OFFSET_X + ($idx * self::SUPPLY_GAP_X)),
                    'y' => $supplyY,
                ];
                $maxBottomY = max($maxBottomY, $supplyY + self::SUPPLY_FOOTPRINT_HEIGHT);
            }

            foreach ($slots[HandleType::Tool->value] ?? [] as $idx => $sKey) {
                $positions[$sKey] = [
                    'x' => $this->snap($hostX + self::SUPPLY_TOOL_OFFSET_X + ($idx * self::SUPPLY_GAP_X)),
                    'y' => $supplyY,
                ];
                $maxBottomY = max($maxBottomY, $supplyY + self::SUPPLY_FOOTPRINT_HEIGHT);
            }
        }

        $orphanSupplyIndex = 0;
        $orphanY = $this->snap($maxBottomY + 48);

        foreach ($supplyKeys as $sKey) {
            if (isset($attachedSupplySet[$sKey])) {
                continue;
            }

            $positions[$sKey] = [
                'x' => $this->snap(self::START_X + ($orphanSupplyIndex * self::SUPPLY_GAP_X)),
                'y' => $orphanY,
            ];
            $orphanSupplyIndex++;
        }

        foreach ($noteKeys as $idx => $nKey) {
            $positions[$nKey] = [
                'x' => $this->snap(self::START_X + ($idx * (self::NOTE_WIDTH + 32))),
                'y' => $this->snap($minY - self::NOTE_OFFSET_Y),
            ];
        }

        return array_map(function (array $node) use ($positions): array {
            $key = (string) $node['key'];

            if (isset($positions[$key])) {
                $node['position'] = $positions[$key];
            }

            return $node;
        }, $nodes);
    }

    private function desiredPosition(
        string $key,
        array $inEdges,
        array $flowOut,
        array $positions,
        array $nodesByKey,
        array $definitions,
    ): array {
        $targetCenters = [];
        $minOrder = 999;

        foreach ($inEdges as $edge) {
            $src = $edge['source'];

            if (! isset($positions[$src])) {
                continue;
            }

            $srcCenterY = $positions[$src]['y'] + ($this->nodeBoxHeight($nodesByKey[$src], $definitions) / 2);
            $siblings = $flowOut[$src] ?? [];
            $siblingCount = count($siblings);
            $order = (int) $edge['order'];
            $minOrder = min($minOrder, $order);

            if ($siblingCount > 1) {
                $sortedOrders = array_values(array_unique(array_map(fn ($e) => (int) $e['order'], $siblings)));
                sort($sortedOrders);
                $rank = array_search($order, $sortedOrders, true);
                $rank = $rank !== false ? (int) $rank : 0;
                $offset = ($rank - ((count($sortedOrders) - 1) / 2)) * self::BRANCH_SPREAD_Y;
                $targetCenters[] = $srcCenterY + $offset;
            } else {
                $targetCenters[] = $srcCenterY;
            }
        }

        if ($targetCenters === []) {
            return ['y' => self::CENTER_Y, 'order' => $minOrder];
        }

        $avgCenterY = array_sum($targetCenters) / count($targetCenters);
        $ownBoxH = $this->nodeBoxHeight($nodesByKey[$key], $definitions);

        return [
            'y' => (int) round($avgCenterY - ($ownBoxH / 2)),
            'order' => $minOrder,
        ];
    }

    private function assignLayers(array $flowKeys, array $flowOut, array $flowIn, array $nodesByKey): array
    {
        if ($flowKeys === []) {
            return [];
        }

        $roots = [];

        foreach ($flowKeys as $key) {
            if (($flowIn[$key] ?? []) === [] || str_starts_with((string) ($nodesByKey[$key]['type'] ?? ''), 'trigger.')) {
                $roots[] = $key;
            }
        }

        if ($roots === []) {
            $roots[] = $flowKeys[0];
        }

        $backEdges = $this->detectBackEdges($flowKeys, $roots, $flowOut);

        $dagOut = [];
        $inDegree = [];

        foreach ($flowKeys as $key) {
            $dagOut[$key] = [];
            $inDegree[$key] = 0;
        }

        foreach ($flowOut as $src => $edges) {
            foreach ($edges as $edge) {
                $tgt = $edge['target'];

                if (isset($backEdges["$src->$tgt"])) {
                    continue;
                }

                $dagOut[$src][] = $tgt;
                $inDegree[$tgt] = ($inDegree[$tgt] ?? 0) + 1;
            }
        }

        $layers = [];
        $queue = [];

        foreach ($flowKeys as $key) {
            if (($inDegree[$key] ?? 0) === 0) {
                $layers[$key] = 0;
                $queue[] = $key;
            }
        }

        while ($queue !== []) {
            $current = array_shift($queue);
            $currentLayer = $layers[$current] ?? 0;

            foreach ($dagOut[$current] ?? [] as $next) {
                $layers[$next] = max($layers[$next] ?? 0, $currentLayer + 1);
                $inDegree[$next]--;

                if ($inDegree[$next] <= 0) {
                    $queue[] = $next;
                }
            }
        }

        foreach ($flowKeys as $key) {
            if (! isset($layers[$key])) {
                $layers[$key] = 0;
            }
        }

        return $layers;
    }

    private function detectBackEdges(array $flowKeys, array $roots, array $flowOut): array
    {
        $state = [];
        $backEdges = [];

        $dfs = function (string $node) use (&$dfs, &$state, &$backEdges, $flowOut): void {
            $state[$node] = 1;

            foreach ($flowOut[$node] ?? [] as $edge) {
                $next = $edge['target'];
                $nextState = $state[$next] ?? 0;

                if ($nextState === 1) {
                    $backEdges["$node->$next"] = true;
                } elseif ($nextState === 0) {
                    $dfs($next);
                }
            }

            $state[$node] = 2;
        };

        foreach ($roots as $root) {
            if (($state[$root] ?? 0) === 0) {
                $dfs($root);
            }
        }

        foreach ($flowKeys as $key) {
            if (($state[$key] ?? 0) === 0) {
                $dfs($key);
            }
        }

        return $backEdges;
    }

    private function handleOrder(array $node, string $handle, array $definitions): int
    {
        $type = (string) ($node['type'] ?? '');

        if ($type === 'logic.switch') {
            $outputs = SwitchNode::allowedOutputsForConfig(is_array($node['config'] ?? null) ? $node['config'] : []);
            $idx = array_search($handle, $outputs, true);

            return $idx !== false ? (int) $idx : 99;
        }

        $def = $definitions[$type] ?? null;

        if ($def instanceof NodeDefinition) {
            $idx = array_search($handle, $def->outputs, true);

            if ($idx !== false) {
                return (int) $idx;
            }
        }

        return match ($handle) {
            HandleType::True->value, HandleType::Case1->value => 0,
            HandleType::False->value, HandleType::Case2->value => 1,
            HandleType::Case3->value => 2,
            HandleType::Default->value => 98,
            default => 0,
        };
    }

    private function isSupplyNode(string $type, array $definitions): bool
    {
        $def = $definitions[$type] ?? null;

        if (! $def instanceof NodeDefinition) {
            return false;
        }

        if ($def->inputs !== [] || $def->outputs === []) {
            return false;
        }

        foreach ($def->outputs as $output) {
            if (! HandleType::isSpecial($output)) {
                return false;
            }
        }

        return true;
    }

    private function outputCount(array $node, array $definitions): int
    {
        $type = (string) ($node['type'] ?? '');

        if ($type === 'logic.switch') {
            return count(SwitchNode::allowedOutputsForConfig(is_array($node['config'] ?? null) ? $node['config'] : []));
        }

        $def = $definitions[$type] ?? null;

        return $def instanceof NodeDefinition ? count($def->outputs) : 1;
    }

    private function nodeBoxHeight(array $node, array $definitions): int
    {
        if (($node['type'] ?? '') === 'ai.agent') {
            return self::AGENT_HEIGHT;
        }

        $outputs = $this->outputCount($node, $definitions);

        if ($outputs > 2) {
            return max(self::STANDARD_HEIGHT, ($outputs * 28) + 16);
        }

        return self::STANDARD_HEIGHT;
    }

    private function nodeFootprintHeight(array $node, array $hostSupplies, array $definitions): int
    {
        $hasSupplies = false;

        foreach ($hostSupplies as $list) {
            if ($list !== []) {
                $hasSupplies = true;
                break;
            }
        }

        if ($hasSupplies) {
            return self::SUPPLY_OFFSET_Y + self::SUPPLY_FOOTPRINT_HEIGHT;
        }

        return $this->nodeBoxHeight($node, $definitions) + self::LABEL_CLEARANCE_Y;
    }

    private function nodeFootprintWidth(array $node, array $hostSupplies): int
    {
        if (($node['type'] ?? '') !== 'ai.agent') {
            return self::STANDARD_WIDTH;
        }

        $toolCount = count($hostSupplies[HandleType::Tool->value] ?? []);

        if ($toolCount <= 1) {
            return self::AGENT_WIDTH;
        }

        $rightmostToolRight = self::SUPPLY_TOOL_OFFSET_X + (($toolCount - 1) * self::SUPPLY_GAP_X) + 80;

        return max(self::AGENT_WIDTH, $rightmostToolRight);
    }

    private function snap(int|float $value): int
    {
        return (int) (round($value / self::GRID) * self::GRID);
    }
}
