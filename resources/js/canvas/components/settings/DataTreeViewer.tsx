import { memo, useCallback, useMemo, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { startFieldDrag, useActiveDragPath, type FieldValueType } from '@canvas/lib/fieldDrag';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

const PAGE_SIZE = 50;

type DataTreeViewerProps = {
    data: unknown;
    basePath?: string;
};

export const hasRenderableData = (data: unknown): boolean => {
    if (data === null || data === undefined) {
        return false;
    }

    if (typeof data === 'object') {
        return Object.keys(data as Record<string, unknown>).length > 0;
    }

    if (typeof data === 'string') {
        return data.trim() !== '';
    }

    return true;
};

export function DataTreeViewer({ data, basePath = 'input' }: DataTreeViewerProps) {
    const [search, setSearch] = useState('');
    const [viewMode, setViewMode] = useState<'tree' | 'json'>('tree');
    const [visibleLimit, setVisibleLimit] = useState(PAGE_SIZE);
    const activeDragPath = useActiveDragPath();

    const copyRawJson = useCallback(() => {
        if (!hasRenderableData(data)) return;
        navigator.clipboard.writeText(JSON.stringify(data, null, 2));
        useWorkflowStore.getState().addToast('داده‌ها به صورت JSON کپی شدند.', 'success', 2000);
    }, [data]);

    const copyExpression = useCallback((expression: string) => {
        navigator.clipboard.writeText(expression);
        useWorkflowStore.getState().addToast(`عبارت ${expression} کپی شد.`, 'success', 2000);
    }, []);

    if (!hasRenderableData(data)) {
        return null;
    }

    if (typeof data !== 'object') {
        return (
            <div
                dir="ltr"
                className="flex items-center justify-between rounded-xl border border-line-border bg-surface-card p-3 font-mono text-xs text-content-main shadow-xs"
            >
                <span className="font-semibold text-emerald-500">value:</span>
                <span className="truncate">{String(data)}</span>
            </div>
        );
    }

    const entries = Object.entries(data as Record<string, unknown>);
    const normalizedSearch = search.trim().toLowerCase();
    const visibleEntries = normalizedSearch !== '' ? entries : entries.slice(0, visibleLimit);
    const remainingCount = normalizedSearch !== '' ? 0 : Math.max(0, entries.length - visibleLimit);

    return (
        <div dir="ltr" className="w-full space-y-2.5 font-mono text-xs select-none text-left">
            <div className="flex items-center justify-between gap-2 border-b border-line-border/60 pb-2">
                <div className="relative flex-1">
                    <Icon
                        name="search"
                        className="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-content-muted"
                    />
                    <input
                        type="text"
                        value={search}
                        placeholder="Search keys..."
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-full rounded-lg border border-line-border bg-surface-card py-1 pl-8 pr-2 text-[11px] text-content-main placeholder:text-content-muted/50 focus:border-primary focus:outline-none"
                    />
                    {search && (
                        <button
                            type="button"
                            onClick={() => setSearch('')}
                            className="absolute right-2 top-1/2 -translate-y-1/2 text-content-muted hover:text-content-main"
                        >
                            <Icon name="x" className="size-3" />
                        </button>
                    )}
                </div>

                <div className="flex items-center gap-1 rounded-lg border border-line-border bg-surface-card p-0.5">
                    <button
                        type="button"
                        onClick={() => setViewMode('tree')}
                        className={`rounded-md px-2 py-0.5 text-[10px] font-semibold transition ${
                            viewMode === 'tree'
                                ? 'bg-primary text-white shadow-xs'
                                : 'text-content-muted hover:text-content-main'
                        }`}
                    >
                        Tree
                    </button>
                    <button
                        type="button"
                        onClick={() => setViewMode('json')}
                        className={`rounded-md px-2 py-0.5 text-[10px] font-semibold transition ${
                            viewMode === 'json'
                                ? 'bg-primary text-white shadow-xs'
                                : 'text-content-muted hover:text-content-main'
                        }`}
                    >
                        JSON
                    </button>
                </div>

                <button
                    type="button"
                    onClick={copyRawJson}
                    title="Copy JSON"
                    className="flex size-7 shrink-0 items-center justify-center rounded-lg border border-line-border bg-surface-card text-content-muted transition hover:border-primary hover:text-primary"
                >
                    <Icon name="copy" className="size-3.5" />
                </button>
            </div>

            {viewMode === 'json' ? (
                <pre className="custom-scrollbar overflow-x-auto rounded-xl border border-line-border bg-surface-card p-3 font-mono text-[11px] leading-5 text-content-main select-text">
                    {JSON.stringify(data, null, 2)}
                </pre>
            ) : (
                <div className="space-y-1">
                    {visibleEntries.map(([key, value]) => (
                        <TreeNode
                            key={key}
                            keyName={key}
                            value={value}
                            path={`${basePath}.${key}`}
                            depth={0}
                            searchTerm={normalizedSearch}
                            activeDragPath={activeDragPath}
                            onCopyExpression={copyExpression}
                        />
                    ))}

                    {remainingCount > 0 && (
                        <button
                            type="button"
                            dir="rtl"
                            onClick={() => setVisibleLimit((prev) => prev + PAGE_SIZE)}
                            className="mt-1.5 w-full rounded-lg border border-dashed border-line-border py-1.5 font-sans text-[11px] font-semibold text-content-muted transition hover:border-primary hover:text-primary"
                        >
                            نمایش موارد بیشتر ({remainingCount} مورد باقی‌مانده)
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

type TreeNodeProps = {
    keyName: string;
    value: unknown;
    path: string;
    depth: number;
    searchTerm: string;
    activeDragPath: string | null;
    onCopyExpression: (expression: string) => void;
};

const TreeNode = memo(function TreeNode({
                                            keyName,
                                            value,
                                            path,
                                            depth,
                                            searchTerm,
                                            activeDragPath,
                                            onCopyExpression,
                                        }: TreeNodeProps) {
    const isObject = value !== null && typeof value === 'object';
    const isArray = Array.isArray(value);
    const childCount = isObject ? Object.keys(value as Record<string, unknown>).length : 0;

    const [isOpen, setIsOpen] = useState(
        () => searchTerm !== '' || (depth < 1 && childCount <= 12),
    );
    const [childLimit, setChildLimit] = useState(PAGE_SIZE);

    const matchesSearch = useMemo(() => {
        if (!searchTerm) return true;
        if (keyName.toLowerCase().includes(searchTerm)) return true;
        if (!isObject && String(value).toLowerCase().includes(searchTerm)) return true;
        if (isObject) {
            return JSON.stringify(value).toLowerCase().includes(searchTerm);
        }
        return false;
    }, [keyName, value, isObject, searchTerm]);

    if (!matchesSearch) {
        return null;
    }

    const typeBadge = isArray
        ? `arr [${value.length}]`
        : isObject
            ? 'obj'
            : typeof value === 'string'
                ? 'str'
                : typeof value === 'number'
                    ? 'num'
                    : typeof value === 'boolean'
                        ? 'bool'
                        : 'null';

    const badgeColor = isArray
        ? 'bg-amber-500/15 text-amber-500 border-amber-500/30'
        : isObject
            ? 'bg-sky-500/15 text-sky-500 border-sky-500/30'
            : typeof value === 'string'
                ? 'bg-emerald-500/15 text-emerald-500 border-emerald-500/30'
                : typeof value === 'number'
                    ? 'bg-purple-500/15 text-purple-500 border-purple-500/30'
                    : 'bg-content-muted/15 text-content-muted border-line-border';

    const expression = `{{ ${path} }}`;

    const inferredFieldType: FieldValueType = isArray
        ? 'array'
        : isObject
            ? 'object'
            : typeof value === 'number'
                ? 'number'
                : typeof value === 'boolean'
                    ? 'boolean'
                    : 'string';

    const isBeingDragged = activeDragPath === path;
    const expanded = isOpen || searchTerm !== '';

    const handlePointerDown = (e: React.PointerEvent<HTMLDivElement>) => {
        startFieldDrag(e, {
            keyName,
            path,
            expression,
            fieldType: inferredFieldType,
        });
    };

    const childEntries = isObject && expanded ? Object.entries(value as Record<string, unknown>) : [];
    const visibleChildren = searchTerm !== '' ? childEntries : childEntries.slice(0, childLimit);
    const remainingChildren = searchTerm !== '' ? 0 : Math.max(0, childEntries.length - childLimit);

    return (
        <div className="w-full text-left font-mono">
            <div
                onPointerDown={handlePointerDown}
                className={`group flex w-full cursor-grab items-center justify-between gap-2 rounded-lg border px-2 py-1 transition active:cursor-grabbing ${
                    isBeingDragged
                        ? 'border-primary/40 bg-primary/8 opacity-60'
                        : 'border-transparent hover:border-line-border hover:bg-surface-raised'
                }`}
            >
                <div className="flex min-w-0 flex-1 items-center gap-1.5 overflow-hidden">
                    {isObject && childCount > 0 ? (
                        <button
                            type="button"
                            onPointerDown={(e) => e.stopPropagation()}
                            onClick={() => setIsOpen((prev) => !prev)}
                            className="flex size-4 shrink-0 items-center justify-center rounded text-content-muted transition hover:bg-line-border hover:text-content-main"
                        >
                            <Icon
                                name="chevron-down"
                                className={`size-3 transition-transform duration-150 ${expanded ? '' : '-rotate-90'}`}
                            />
                        </button>
                    ) : (
                        <span className="size-4 shrink-0" />
                    )}

                    <span
                        className={`shrink-0 rounded border px-1 py-0.2 text-[8px] font-bold uppercase leading-tight ${badgeColor}`}
                    >
                        {typeBadge}
                    </span>

                    <span className="shrink-0 font-bold text-content-main">{keyName}:</span>

                    {!isObject && (
                        <span
                            dir="auto"
                            className={`min-w-0 flex-1 truncate text-[11px] ${
                                typeof value === 'string'
                                    ? 'text-emerald-500 dark:text-emerald-400'
                                    : typeof value === 'number'
                                        ? 'font-bold text-purple-500 dark:text-purple-400'
                                        : typeof value === 'boolean'
                                            ? 'font-bold text-sky-500'
                                            : 'text-content-muted'
                            }`}
                        >
                            {typeof value === 'string' ? `"${value}"` : String(value)}
                        </span>
                    )}
                </div>

                <div className="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                    <button
                        type="button"
                        onPointerDown={(e) => e.stopPropagation()}
                        onClick={(e) => {
                            e.stopPropagation();
                            onCopyExpression(expression);
                        }}
                        title={`Copy ${expression}`}
                        className="flex size-6 items-center justify-center rounded text-content-muted transition hover:bg-primary/10 hover:text-primary"
                    >
                        <Icon name="copy" className="size-3" />
                    </button>
                </div>
            </div>

            {isObject && expanded && visibleChildren.length > 0 && (
                <div className="mt-0.5 ml-3.5 space-y-0.5 border-l border-line-border/60 pl-2">
                    {visibleChildren.map(([k, v]) => (
                        <TreeNode
                            key={k}
                            keyName={k}
                            value={v}
                            path={isArray ? `${path}[${k}]` : `${path}.${k}`}
                            depth={depth + 1}
                            searchTerm={searchTerm}
                            activeDragPath={activeDragPath}
                            onCopyExpression={onCopyExpression}
                        />
                    ))}

                    {remainingChildren > 0 && (
                        <button
                            type="button"
                            dir="rtl"
                            onClick={() => setChildLimit((prev) => prev + PAGE_SIZE)}
                            className="mt-1 w-full rounded-md border border-dashed border-line-border py-1 font-sans text-[10px] font-semibold text-content-muted transition hover:border-primary hover:text-primary"
                        >
                            نمایش بیشتر ({remainingChildren} مورد)
                        </button>
                    )}
                </div>
            )}
        </div>
    );
});
