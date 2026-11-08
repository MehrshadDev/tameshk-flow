import {
    useCallback,
    useEffect,
    useRef,
    useState,
    type PointerEvent as ReactPointerEvent,
    type RefObject,
} from 'react';

type Options = {
    storageKey: string;
    min: number;
    max: number;
    initial: number;
};

const readWidth = ({ storageKey, min, max, initial }: Options): number => {
    const saved = Number(localStorage.getItem(storageKey));

    return Number.isFinite(saved) && saved >= min && saved <= max ? saved : initial;
};

export function useResizablePanel(options: Options): {
    panelRef: RefObject<HTMLElement | null>;
    width: number;
    isResizing: boolean;
    onPointerDown: (event: ReactPointerEvent<HTMLElement>) => void;
    reset: () => void;
} {
    const { storageKey, min, max, initial } = options;

    const panelRef = useRef<HTMLElement | null>(null);
    const [width, setWidth] = useState(() => readWidth(options));
    const [isResizing, setIsResizing] = useState(false);
    const origin = useRef({ x: 0, width: 0 });
    const latest = useRef(width);
    const rafId = useRef<number | null>(null);

    const onPointerDown = useCallback((event: ReactPointerEvent<HTMLElement>) => {
        if (event.button !== 0) return;

        event.preventDefault();
        event.stopPropagation();

        try {
            event.currentTarget.setPointerCapture(event.pointerId);
        } catch {}

        const currentWidth = panelRef.current?.getBoundingClientRect().width ?? latest.current;
        latest.current = currentWidth;
        origin.current = { x: event.clientX, width: currentWidth };
        setIsResizing(true);
    }, []);

    const reset = useCallback(() => {
        latest.current = initial;
        if (panelRef.current) {
            panelRef.current.style.width = `${initial}px`;
        }
        setWidth(initial);
        localStorage.setItem(storageKey, String(initial));
    }, [initial, storageKey]);

    useEffect(() => {
        if (!isResizing) return;

        const onMove = (event: PointerEvent) => {
            const next = origin.current.width + (origin.current.x - event.clientX);
            const clamped = Math.min(max, Math.max(min, next));
            latest.current = clamped;

            if (rafId.current !== null) {
                cancelAnimationFrame(rafId.current);
            }

            rafId.current = requestAnimationFrame(() => {
                if (panelRef.current) {
                    panelRef.current.style.width = `${clamped}px`;
                }
                rafId.current = null;
            });
        };

        const onUp = () => {
            if (rafId.current !== null) {
                cancelAnimationFrame(rafId.current);
                rafId.current = null;
            }

            setIsResizing(false);
            setWidth(latest.current);
            localStorage.setItem(storageKey, String(latest.current));
        };

        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
        window.addEventListener('pointermove', onMove, { passive: true });
        window.addEventListener('pointerup', onUp);
        window.addEventListener('pointercancel', onUp);

        return () => {
            if (rafId.current !== null) {
                cancelAnimationFrame(rafId.current);
            }
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
            window.removeEventListener('pointercancel', onUp);
        };
    }, [isResizing, min, max, storageKey]);

    return { panelRef, width, isResizing, onPointerDown, reset };
}
