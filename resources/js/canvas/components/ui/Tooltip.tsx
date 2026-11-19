import { cloneElement, useEffect, useLayoutEffect, useRef, useState, type ReactElement } from 'react';
import { createPortal } from 'react-dom';

type Side = 'top' | 'bottom' | 'left' | 'right';

type TooltipProps = {
    label: string;
    side?: Side;
    shortcut?: string;
    delay?: number;
    children: ReactElement<any>;
};

const GAP = 8;
const MARGIN = 12;

const clamp = (value: number, min: number, max: number) =>
    max < min ? min : Math.min(Math.max(value, min), max);

export function Tooltip({ label, side = 'top', shortcut, delay = 300, children }: TooltipProps) {
    const [rect, setRect] = useState<DOMRect | null>(null);
    const timer = useRef<number | null>(null);
    const tooltipRef = useRef<HTMLDivElement>(null);

    const clear = () => {
        if (timer.current !== null) {
            window.clearTimeout(timer.current);
            timer.current = null;
        }
    };

    const show = (element: HTMLElement) => {
        clear();
        timer.current = window.setTimeout(() => {
            setRect(element.getBoundingClientRect());
        }, delay);
    };

    const hide = () => {
        clear();
        setRect(null);
    };

    useEffect(() => clear, []);

    useLayoutEffect(() => {
        const el = tooltipRef.current;
        if (!rect || !el) return;

        const tipWidth = el.offsetWidth;
        const tipHeight = el.offsetHeight;
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        const maxLeft = window.innerWidth - MARGIN - tipWidth;
        const maxTop = window.innerHeight - MARGIN - tipHeight;

        let left = 0;
        let top = 0;

        switch (side) {
            case 'bottom':
                left = clamp(centerX - tipWidth / 2, MARGIN, maxLeft);
                top = clamp(rect.bottom + GAP, MARGIN, maxTop);
                break;
            case 'left':
                left = clamp(rect.left - GAP - tipWidth, MARGIN, maxLeft);
                top = clamp(centerY - tipHeight / 2, MARGIN, maxTop);
                break;
            case 'right':
                left = clamp(rect.right + GAP, MARGIN, maxLeft);
                top = clamp(centerY - tipHeight / 2, MARGIN, maxTop);
                break;
            default:
                left = clamp(centerX - tipWidth / 2, MARGIN, maxLeft);
                top = clamp(rect.top - GAP - tipHeight, MARGIN, maxTop);
                break;
        }

        el.style.left = `${left}px`;
        el.style.top = `${top}px`;

        const frame = window.requestAnimationFrame(() => {
            el.style.opacity = '1';
        });

        return () => window.cancelAnimationFrame(frame);
    }, [rect, side, label, shortcut]);

    const original = children.props;

    const trigger = cloneElement(children, {
        onMouseEnter: (event: React.MouseEvent<HTMLElement>) => {
            original.onMouseEnter?.(event);
            show(event.currentTarget);
        },
        onMouseLeave: (event: React.MouseEvent<HTMLElement>) => {
            original.onMouseLeave?.(event);
            hide();
        },
        onMouseDown: (event: React.MouseEvent<HTMLElement>) => {
            original.onMouseDown?.(event);
            hide();
        },
        onFocus: (event: React.FocusEvent<HTMLElement>) => {
            original.onFocus?.(event);
            show(event.currentTarget);
        },
        onBlur: (event: React.FocusEvent<HTMLElement>) => {
            original.onBlur?.(event);
            hide();
        },
    });

    return (
        <>
            {trigger}
            {rect &&
                createPortal(
                    <div
                        ref={tooltipRef}
                        dir="rtl"
                        role="tooltip"
                        className="pointer-events-none fixed z-100 flex max-w-60 items-center gap-2 rounded-xl border border-line-border bg-surface-raised/95 px-3 py-1.5 text-[11px] font-semibold leading-5 text-content-main opacity-0 shadow-2xl backdrop-blur-md transition-opacity duration-100"
                    >
                        <span>{label}</span>
                        {shortcut && (
                            <kbd
                                dir="ltr"
                                className="rounded-md border border-line-border bg-surface-card px-1.5 py-px font-mono text-[10px] font-medium text-content-muted"
                            >
                                {shortcut}
                            </kbd>
                        )}
                    </div>,
                    document.body,
                )}
        </>
    );
}
