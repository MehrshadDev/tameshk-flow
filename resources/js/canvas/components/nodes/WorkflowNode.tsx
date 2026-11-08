import { Handle, NodeToolbar, Position, useConnection, useUpdateNodeInternals, type NodeProps } from '@xyflow/react';
import { useLayoutEffect } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { Tooltip } from '@canvas/components/ui/Tooltip';
import type { FlowNode } from '@canvas/lib/graph';
import { missingRequiredLabels } from '@canvas/lib/fields';
import { Handle as HandleId, SPECIAL_HANDLES } from '@canvas/lib/handles';
import { resolveNodeOutputs } from '@canvas/lib/nodeOutputs';
import { handleLabels, nodeAppearance } from '@canvas/lib/nodeStyles';
import { isTestableTrigger } from '@canvas/lib/triggers';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import { AiAgentNodeCard } from './AiAgentNodeCard';
import { DOT_HANDLE, handleTop, NodeBadge, SidePlus } from './NodeParts';
import { SupplyNodeCard } from './SupplyNodeCard';

export function WorkflowNode({ id, data, selected }: NodeProps<FlowNode>) {
    const updateNodeInternals = useUpdateNodeInternals();
    const { inProgress } = useConnection();

    const nodes = useWorkflowStore((state) => state.nodes);
    const definitions = useWorkflowStore((state) => state.definitions);
    const ids = useWorkflowStore((state) => state.ids);
    const lastRun = useWorkflowStore((state) => state.lastRun);
    const startConnection = useWorkflowStore((state) => state.startConnection);
    const startTargetConnection = useWorkflowStore((state) => state.startTargetConnection);
    const connectingHandle = useWorkflowStore((state) => state.connectingHandle);
    const edges = useWorkflowStore((state) => state.edges);
    const deleteNode = useWorkflowStore((state) => state.deleteNode);
    const toggleNodeDisabled = useWorkflowStore((state) => state.toggleNodeDisabled);
    const executeSingleNode = useWorkflowStore((state) => state.executeSingleNode);
    const duplicateSelected = useWorkflowStore((state) => state.duplicateSelected);
    const runningNodeId = useWorkflowStore((state) => state.runningNodeId);

    const definition = definitions.find((item) => item.type === data.nodeType);
    const dbId = ids[id] ?? Number(id);

    const isManualTrigger = nodes.some((n) => isTestableTrigger(n.data.nodeType));

    const nodeRuns = lastRun?.node_runs?.filter((r) => r.node_id === dbId) ?? [];
    const lastRunItem = nodeRuns.length > 0 ? nodeRuns[nodeRuns.length - 1] : undefined;
    const successCount = nodeRuns.filter((r) => r.status === 'success').length;

    const isAiAgent = data.nodeType === 'ai.agent';
    const isTrigger = definition?.category === 'trigger';
    const isCondition = data.nodeType === 'logic.condition';
    const isSwitch = data.nodeType === 'logic.switch';
    const isDisabled = Boolean(data.config?.disabled) && !isTrigger;
    const isNodeExecuting = runningNodeId === id;

    const hasEverRun = lastRun !== null;
    const missingFields =
        hasEverRun && definition && !isDisabled
            ? missingRequiredLabels(definition.configSchema, data.config ?? {})
            : [];

    const inputs = definition?.inputs ?? [];
    const resolvedOutputs = resolveNodeOutputs(data.nodeType, data.config, definition);
    const outputs = resolvedOutputs.map((o) => o.id);
    const labeled = resolvedOutputs.length > 1;
    const { iconName, iconStyle } = nodeAppearance(definition);

    const isSupplyNode =
        outputs.length === 1 &&
        (SPECIAL_HANDLES as readonly string[]).includes(outputs[0]) &&
        inputs.length === 0;

    const isSupplyConnected = isSupplyNode && edges.some((edge) => edge.source === id);

    const isSlotConnected = (handle: string) =>
        edges.some((edge) => edge.target === id && edge.targetHandle === handle);

    const hasActiveModel = edges.some((edge) => {
        if (edge.target !== id || edge.targetHandle !== HandleId.ChatModel) return false;
        const src = nodes.find((n) => n.id === edge.source);
        return Boolean(src && !src.data.config?.disabled);
    });

    const isMissingModel = hasEverRun && isAiAgent && !hasActiveModel;
    const isForeignConnecting = Boolean(connectingHandle && connectingHandle.nodeId !== id);

    const conditionOutcome =
        isCondition && lastRunItem?.status === 'success'
            ? ((lastRunItem.output_data as { handle?: string } | null)?.handle ?? null)
            : null;

    const switchOutputData =
        isSwitch && lastRunItem?.status === 'success'
            ? (lastRunItem.output_data as { _matched_case?: string | null; _matched_label?: string | null } | null)
            : null;

    const switchOutcome = switchOutputData?._matched_case ?? null;
    const switchOutcomeLabel =
        switchOutputData?._matched_label ??
        (switchOutcome
            ? (resolvedOutputs.find((o) => o.id === switchOutcome)?.label ?? handleLabels[switchOutcome] ?? switchOutcome)
            : null);

    const outputsSignature = resolvedOutputs.map((o) => `${o.id}:${o.label}`).join('|');
    const dynamicHeightPx = resolvedOutputs.length > 2 ? Math.max(72, resolvedOutputs.length * 28 + 16) : 72;

    useLayoutEffect(() => {
        updateNodeInternals(id);
    }, [id, edges.length, outputsSignature, updateNodeInternals]);

    return (
        <div
            className={`group/node pointer-events-none relative flex flex-col items-center ${
                isDisabled ? 'opacity-50' : ''
            }`}
        >
            <NodeToolbar
                position={Position.Top}
                offset={8}
                className="flex items-center gap-1 rounded-xl border border-line-border bg-surface-raised p-1 shadow-xl backdrop-blur-md"
            >
                {isManualTrigger && (
                    <Tooltip label="تست این مرحله">
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                void executeSingleNode(id);
                            }}
                            disabled={isNodeExecuting}
                            className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-emerald-500/10 hover:text-emerald-500 disabled:opacity-50"
                        >
                            <Icon name="player-play" className="size-3.5" />
                        </button>
                    </Tooltip>
                )}

                {!isTrigger && (
                    <Tooltip label={isDisabled ? 'فعال کردن نود' : 'غیرفعال کردن نود'}>
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                toggleNodeDisabled(id);
                            }}
                            className={`flex size-6 items-center justify-center rounded-lg transition ${
                                isDisabled
                                    ? 'bg-amber-500/10 text-amber-500'
                                    : 'text-content-muted hover:bg-line-border/60 hover:text-content-main'
                            }`}
                        >
                            <Icon name="power" className="size-3.5" />
                        </button>
                    </Tooltip>
                )}

                {!isTrigger && (
                    <Tooltip label="تکثیر نود (Duplicate)">
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                duplicateSelected();
                            }}
                            className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                        >
                            <Icon name="copy" className="size-3.5" />
                        </button>
                    </Tooltip>
                )}

                <Tooltip label="حذف نود">
                    <button
                        type="button"
                        onClick={(e) => {
                            e.stopPropagation();
                            deleteNode(id);
                        }}
                        className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                    >
                        <Icon name="trash" className="size-3.5" />
                    </button>
                </Tooltip>
            </NodeToolbar>

            {isAiAgent ? (
                <AiAgentNodeCard
                    id={id}
                    data={data}
                    selected={selected}
                    isDisabled={isDisabled}
                    isNodeExecuting={isNodeExecuting}
                    missingFields={missingFields}
                    lastRunItem={lastRunItem}
                    successCount={successCount}
                    isMissingModel={isMissingModel}
                    edges={edges}
                    inProgress={inProgress}
                    isForeignConnecting={isForeignConnecting}
                    connectingHandle={connectingHandle}
                    isSlotConnected={isSlotConnected}
                    startConnection={startConnection}
                    startTargetConnection={startTargetConnection}
                />
            ) : isSupplyNode ? (
                <SupplyNodeCard
                    outputHandle={outputs[0]}
                    selected={selected}
                    isDisabled={isDisabled}
                    isNodeExecuting={isNodeExecuting}
                    missingFields={missingFields}
                    lastRunItem={lastRunItem}
                    successCount={successCount}
                    isSupplyConnected={isSupplyConnected}
                    isForeignConnecting={isForeignConnecting}
                    connectingHandle={connectingHandle}
                    iconName={iconName}
                    iconStyle={iconStyle}
                />
            ) : (
                <div
                    style={{ height: `${dynamicHeightPx}px` }}
                    className={`pointer-events-auto relative flex w-18 items-center justify-center rounded-2xl border bg-surface-card shadow-sm transition-all duration-200 ${
                        selected
                            ? 'border-primary shadow-lg ring-4 ring-primary/20'
                            : isNodeExecuting
                                ? 'border-amber-500 shadow-lg ring-4 ring-amber-500/20'
                                : 'border-line-border hover:border-content-muted/40'
                    } ${isDisabled ? 'border-dashed' : ''}`}
                >
                    <NodeBadge
                        disabled={isDisabled}
                        executing={isNodeExecuting}
                        missing={missingFields}
                        status={lastRunItem?.status}
                        successCount={successCount}
                    />

                    {inputs.map((handleId, index) => {
                        const top = handleTop(index, inputs.length);
                        return (
                            <div
                                key={handleId}
                                style={{ top }}
                                className="pointer-events-none absolute -left-1.5 z-10 flex -translate-y-1/2 items-center"
                            >
                                <Handle id={handleId} type="target" position={Position.Left} className={DOT_HANDLE} />
                            </div>
                        );
                    })}

                    <div className={`flex size-11 items-center justify-center rounded-xl shadow-inner ${iconStyle}`}>
                        <Icon name={iconName} className="size-6" />
                    </div>

                    {resolvedOutputs.map((outItem, index) => {
                        const handleId = outItem.id;
                        const labelText = outItem.label;
                        const top = handleTop(index, resolvedOutputs.length);
                        const isConnected = edges.some(
                            (edge) => edge.source === id && (edge.sourceHandle ?? HandleId.Main) === handleId,
                        );
                        const showPlus = !isConnected && !inProgress;

                        const badgeStyle =
                            handleId === HandleId.True
                                ? 'border-emerald-500/30 text-emerald-500'
                                : handleId === HandleId.False
                                    ? 'border-rose-500/30 text-rose-400'
                                    : handleId === HandleId.Default
                                        ? 'border-amber-500/30 text-amber-500'
                                        : 'border-line-border text-content-muted';

                        return (
                            <div
                                key={handleId}
                                style={{ top }}
                                className="pointer-events-none absolute -right-1.5 z-10 flex -translate-y-1/2 items-center"
                            >
                                <Handle id={handleId} type="source" position={Position.Right} className={DOT_HANDLE}>
                                    {showPlus && (
                                        <SidePlus
                                            labeled={labeled}
                                            labelText={labelText}
                                            badgeStyle={badgeStyle}
                                            onClick={() => startConnection(id, handleId)}
                                        />
                                    )}
                                </Handle>

                                {labeled && !showPlus && (
                                    <span
                                        dir="rtl"
                                        className={`pointer-events-none absolute left-5 top-1/2 max-w-28 -translate-y-1/2 truncate rounded-md border bg-surface-raised px-1.5 py-0.5 text-[9px] font-bold leading-none whitespace-nowrap shadow-xs ${badgeStyle}`}
                                    >
                                        {labelText}
                                    </span>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}

            {!isAiAgent && (
                <div
                    dir="auto"
                    className={`pointer-events-auto absolute top-full left-1/2 mt-2 -translate-x-1/2 text-center select-none ${
                        isSupplyNode ? 'w-24' : 'w-36'
                    }`}
                >
                    <p
                        className={`text-xs font-bold text-content-main ${
                            isSupplyNode ? 'line-clamp-2 wrap-break-word leading-4' : 'truncate'
                        }`}
                    >
                        {data.label}
                    </p>

                    {conditionOutcome && (
                        <span
                            className={`mt-0.5 inline-block rounded px-1.5 py-[0.2rem] text-[9px] font-bold ${
                                conditionOutcome === HandleId.True
                                    ? 'bg-emerald-500/10 text-emerald-500'
                                    : 'bg-primary/10 text-primary'
                            }`}
                        >
                            {conditionOutcome === HandleId.True ? 'نتیجه: بله' : 'نتیجه: خیر'}
                        </span>
                    )}

                    {switchOutcomeLabel && (
                        <span className="mt-0.5 inline-block rounded bg-orange-500/10 px-1.5 py-[0.2rem] text-[9px] font-bold text-orange-500">
                            مسیر: {switchOutcomeLabel}
                        </span>
                    )}

                    {!conditionOutcome &&
                        !switchOutcomeLabel &&
                        lastRunItem?.duration_ms !== null &&
                        lastRunItem?.duration_ms !== undefined && (
                            <p className="mt-0.5 text-[10px] text-content-muted" dir="ltr">
                                {lastRunItem.duration_ms} ms
                            </p>
                        )}

                    {!conditionOutcome &&
                        !switchOutcomeLabel &&
                        (lastRunItem?.duration_ms === null || lastRunItem?.duration_ms === undefined) && (
                            <>
                                {!definition && (
                                    <p className="mt-0.5 truncate text-[10px] text-primary">نوع ناشناخته</p>
                                )}
                                {definition && !isSupplyNode && definition.name !== data.label && (
                                    <p className="mt-0.5 truncate text-[10px] text-content-muted">
                                        {definition.name}
                                    </p>
                                )}
                            </>
                        )}
                </div>
            )}
        </div>
    );
}
