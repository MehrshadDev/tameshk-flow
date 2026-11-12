import { useId } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import type { NodeRunStatus } from '@canvas/types/graph';

export const HANDLE_RESET = 'relative! left-auto! right-auto! top-auto! bottom-auto! transform-none!';

export const DOT_HANDLE = `${HANDLE_RESET} pointer-events-auto size-3.5! rounded-full! border-2! border-surface-card! bg-content-muted/70! shadow-sm ring-1 ring-line-border transition-all duration-150 cursor-crosshair hover:scale-125 hover:bg-primary! hover:ring-primary/40`;

export const DIAMOND_HANDLE = `${HANDLE_RESET} pointer-events-auto size-6! rounded-none! border-0! bg-transparent! cursor-pointer flex items-center justify-center`;

const BADGE_BASE =
    'absolute -top-1.5 -right-1.5 z-20 flex h-5 min-w-5 items-center justify-center gap-0.5 rounded-full px-1 text-white shadow-md ring-2 ring-surface-card';

export type NodeBadgeProps = {
    disabled: boolean;
    executing: boolean;
    listening?: boolean;
    missing: string[];
    status?: NodeRunStatus;
    successCount: number;
};

export const handleTop = (index: number, total: number) => {
    if (total <= 1) {
        return '50%';
    }
    if (total === 2) {
        return index === 0 ? '30%' : '70%';
    }
    return `${((index + 1) / (total + 1)) * 100}%`;
};

export function SidePlus({
                             onClick,
                             labeled = false,
                             labelText,
                             badgeStyle = 'border-line-border text-content-muted',
                         }: {
    onClick: () => void;
    labeled?: boolean;
    labelText?: string;
    badgeStyle?: string;
}) {
    return (
        <div
            onClick={(e) => {
                e.stopPropagation();
                onClick();
            }}
            className="tameshk-plus-wrapper group/plus pointer-events-auto absolute inset-s-full top-1/2 flex -translate-y-1/2 cursor-pointer items-center"
        >
            <div
                className={`relative flex items-center justify-center ${
                    labeled ? 'min-w-18 px-3' : 'w-5 group-hover/plus:w-7'
                } transition-all duration-150`}
            >
                <span className="pointer-events-none absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-linear-to-r from-content-muted/65 to-content-muted/25 transition-all duration-150 group-hover/plus:from-primary group-hover/plus:to-primary/40" />
                {labeled && labelText && (
                    <span
                        dir="rtl"
                        className={`pointer-events-none relative z-10 max-w-28 truncate rounded-md border bg-surface-raised px-1.5 py-0.5 text-[9px] font-bold leading-none whitespace-nowrap shadow-xs transition-colors group-hover/plus:border-primary/50 ${badgeStyle}`}
                    >
                        {labelText}
                    </span>
                )}
            </div>

            <span className="flex size-6 shrink-0 items-center justify-center rounded-full border border-line-border bg-surface-raised text-content-muted shadow-md transition-all duration-150 group-hover/plus:scale-110 group-hover/plus:border-primary group-hover/plus:bg-primary group-hover/plus:text-white group-hover/plus:shadow-lg group-hover/plus:shadow-primary/30">
                <Icon name="plus" className="pointer-events-none size-3.5" />
            </span>
        </div>
    );
}

export function NodeSnakeBorder({ height }: { height: number }) {
    const uid = useId().replace(/:/g, '');
    const w = 72;
    const h = height;

    return (
        <div className="pointer-events-none absolute inset-0 z-10 overflow-visible rounded-2xl">
            <span className="absolute -inset-1.5 animate-pulse rounded-3xl bg-amber-500/15 blur-md" />
            <svg
                className="pointer-events-none absolute -inset-[2px] size-[calc(100%+4px)] overflow-visible"
                viewBox={`-2 -2 ${w + 4} ${h + 4}`}
                fill="none"
            >
                <defs>
                    <linearGradient id={`snake-grad-${uid}`} x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stopColor="#fde047" />
                        <stop offset="50%" stopColor="#f59e0b" />
                        <stop offset="100%" stopColor="#ea580c" />
                    </linearGradient>
                </defs>
                <rect
                    x="0"
                    y="0"
                    width={w}
                    height={h}
                    rx="16"
                    ry="16"
                    pathLength="100"
                    stroke="rgba(245, 158, 11, 0.25)"
                    strokeWidth="2.5"
                />
                <rect
                    x="0"
                    y="0"
                    width={w}
                    height={h}
                    rx="16"
                    ry="16"
                    pathLength="100"
                    stroke={`url(#snake-grad-${uid})`}
                    strokeWidth="3.5"
                    strokeLinecap="round"
                    strokeDasharray="32 68"
                    style={{
                        filter: 'drop-shadow(0 0 6px rgba(245, 158, 11, 0.85))',
                    }}
                >
                    <animate
                        attributeName="stroke-dashoffset"
                        from="100"
                        to="0"
                        dur="1.5s"
                        repeatCount="indefinite"
                    />
                </rect>
            </svg>
        </div>
    );
}

export function NodeBadge({ disabled, executing, listening = false, missing, status, successCount }: NodeBadgeProps) {
    if (disabled) {
        return null;
    }

    if (listening) {
        return (
            <span className={`${BADGE_BASE} bg-amber-500 shadow-amber-500/40`}>
                <span className="absolute inset-0 animate-ping rounded-full bg-amber-400 opacity-75" />
                <Icon name="loader" className="relative size-3 animate-spin" />
            </span>
        );
    }

    if (executing) {
        return (
            <span className={`${BADGE_BASE} bg-amber-500`}>
                <Icon name="loader" className="size-3 animate-spin" />
            </span>
        );
    }

    if (status === 'failed') {
        return (
            <span className={`${BADGE_BASE} bg-primary`}>
                <Icon name="x" className="size-3" />
            </span>
        );
    }

    if (status === 'success') {
        return (
            <span className={`${BADGE_BASE} bg-emerald-500`}>
                <Icon name="check" className="size-3" />
                {successCount > 1 && (
                    <span className="font-mono text-[9px] font-bold leading-none" dir="ltr">
                        {successCount}
                    </span>
                )}
            </span>
        );
    }

    if (missing.length > 0) {
        return (
            <span title={`فیلدهای ناقص: ${missing.join('، ')}`} className={`${BADGE_BASE} bg-amber-500`}>
                <Icon name="alert-triangle" className="size-3" />
            </span>
        );
    }

    return null;
}
