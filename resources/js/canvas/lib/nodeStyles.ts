import type { NodeCategory, NodeDefinition } from '@canvas/types/graph';
import { Handle } from '@canvas/lib/handles';

type CategoryMeta = {
    label: string;
    style: string;
    color: string;
    order: number;
};

type Palette = {
    style: string;
    color: string;
};

export const CATEGORY_META: Record<NodeCategory, CategoryMeta> = {
    trigger: {
        label: 'شروع‌کننده‌ها',
        style: 'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-500',
        color: '#10b981',
        order: 0,
    },
    ai: {
        label: 'هوش مصنوعی',
        style: 'bg-purple-500/15 text-purple-600 dark:bg-purple-500/10 dark:text-purple-500',
        color: '#a855f7',
        order: 1,
    },
    action: {
        label: 'عملیات و سرویس‌ها',
        style: 'bg-primary/15 text-primary dark:bg-primary/10',
        color: '#e21c48',
        order: 2,
    },
    logic: {
        label: 'منطق و کنترل مسیر',
        style: 'bg-amber-500/15 text-amber-600 dark:bg-amber-500/10 dark:text-amber-500',
        color: '#f59e0b',
        order: 3,
    },
    transform: {
        label: 'تبدیل و تغییر داده',
        style: 'bg-sky-500/15 text-sky-600 dark:bg-sky-500/10 dark:text-sky-500',
        color: '#0ea5e9',
        order: 4,
    },
};

export const NODE_PALETTE: Record<string, Palette> = {
    emerald: {
        style: 'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-500',
        color: '#10b981',
    },
    teal: {
        style: 'bg-teal-500/15 text-teal-600 dark:bg-teal-500/10 dark:text-teal-500',
        color: '#14b8a6',
    },
    cyan: {
        style: 'bg-cyan-500/15 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-500',
        color: '#06b6d4',
    },
    sky: {
        style: 'bg-sky-500/15 text-sky-600 dark:bg-sky-500/10 dark:text-sky-500',
        color: '#0ea5e9',
    },
    blue: {
        style: 'bg-blue-500/15 text-blue-600 dark:bg-blue-500/10 dark:text-blue-500',
        color: '#3b82f6',
    },
    indigo: {
        style: 'bg-indigo-500/15 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-500',
        color: '#6366f1',
    },
    violet: {
        style: 'bg-violet-500/15 text-violet-600 dark:bg-violet-500/10 dark:text-violet-500',
        color: '#8b5cf6',
    },
    fuchsia: {
        style: 'bg-fuchsia-500/15 text-fuchsia-600 dark:bg-fuchsia-500/10 dark:text-fuchsia-500',
        color: '#d946ef',
    },
    pink: {
        style: 'bg-pink-500/15 text-pink-600 dark:bg-pink-500/10 dark:text-pink-500',
        color: '#ec4899',
    },
    rose: {
        style: 'bg-rose-500/15 text-rose-600 dark:bg-rose-500/10 dark:text-rose-500',
        color: '#f43f5e',
    },
    orange: {
        style: 'bg-orange-500/15 text-orange-600 dark:bg-orange-500/10 dark:text-orange-500',
        color: '#f97316',
    },
    amber: {
        style: 'bg-amber-500/15 text-amber-600 dark:bg-amber-500/10 dark:text-amber-500',
        color: '#f59e0b',
    },
    yellow: {
        style: 'bg-yellow-500/15 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-500',
        color: '#eab308',
    },
    lime: {
        style: 'bg-lime-500/15 text-lime-600 dark:bg-lime-500/10 dark:text-lime-500',
        color: '#84cc16',
    },
};

export const categoryStyles: Record<NodeCategory, string> = Object.fromEntries(
    Object.entries(CATEGORY_META).map(([key, meta]) => [key, meta.style]),
) as Record<NodeCategory, string>;

export const categoryColors: Record<NodeCategory, string> = Object.fromEntries(
    Object.entries(CATEGORY_META).map(([key, meta]) => [key, meta.color]),
) as Record<NodeCategory, string>;

export const categoryLabels: Record<NodeCategory, string> = Object.fromEntries(
    Object.entries(CATEGORY_META).map(([key, meta]) => [key, meta.label]),
) as Record<NodeCategory, string>;

export const categoryOrder: NodeCategory[] = (Object.keys(CATEGORY_META) as NodeCategory[])
    .sort((a, b) => CATEGORY_META[a].order - CATEGORY_META[b].order);

export const handleLabels: Record<string, string> = {
    [Handle.Main]: 'اصلی',
    [Handle.True]: 'بله',
    [Handle.False]: 'خیر',
    [Handle.Case1]: 'حالت ۱',
    [Handle.Case2]: 'حالت ۲',
    [Handle.Case3]: 'حالت ۳',
    [Handle.Default]: 'پیش‌فرض',
    [Handle.ChatModel]: 'مدل چت',
    [Handle.Memory]: 'حافظه',
    [Handle.Tool]: 'ابزار',
};

const paletteOf = (definition?: NodeDefinition): Palette | undefined =>
    definition?.color ? NODE_PALETTE[definition.color] : undefined;

export const nodeColor = (definition?: NodeDefinition): string =>
    paletteOf(definition)?.color ?? (definition ? categoryColors[definition.category] : '#737373');

export const nodeAppearance = (
    definition?: NodeDefinition,
    config?: Record<string, unknown>,
) => {
    let resolvedIcon = definition ? (definition.icon ?? 'box') : 'alert-triangle';

    if (
        definition?.type === 'transform.crypto' &&
        config &&
        (config.operation === 'random_int' || config.operation === 'otp')
    ) {
        resolvedIcon = 'dice-3';
    }

    return {
        iconName: resolvedIcon,
        iconStyle: definition
            ? (paletteOf(definition)?.style ?? CATEGORY_META[definition.category].style)
            : 'bg-line-border/60 text-content-muted',
    };
};
