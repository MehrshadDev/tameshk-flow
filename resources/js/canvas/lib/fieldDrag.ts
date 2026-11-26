import {useEffect, useId, useRef, useSyncExternalStore} from 'react';

export type FieldValueType = 'string' | 'number' | 'boolean' | 'array' | 'object';

export type DraggedField = {
    keyName: string;
    path: string;
    expression: string;
    fieldType: FieldValueType;
};

type DragSnapshot = {
    activeField: DraggedField | null;
    hoveredDropId: string | null;
};

type DropHandler = (field: DraggedField) => void;

const DRAG_THRESHOLD_PX = 4;

let snapshot: DragSnapshot = {
    activeField: null,
    hoveredDropId: null,
};

let ghostEl: HTMLDivElement | null = null;
let lastPointer = {x: 0, y: 0};

const listeners = new Set<() => void>();
const dropHandlers = new Map<string, DropHandler>();

const emit = () => {
    listeners.forEach((listener) => listener());
};

const setSnapshot = (next: DragSnapshot) => {
    if (
        snapshot.activeField === next.activeField &&
        snapshot.hoveredDropId === next.hoveredDropId
    ) {
        return;
    }
    snapshot = next;
    emit();
};

const positionGhost = (x: number, y: number) => {
    if (ghostEl) {
        ghostEl.style.transform = `translate3d(${x + 12}px, ${y - 16}px, 0)`;
    }
};

const findDropIdAt = (x: number, y: number): string | null => {
    const el = document.elementFromPoint(x, y);
    const target = el?.closest<HTMLElement>('[data-field-drop-id]');
    return target?.dataset.fieldDropId ?? null;
};

export function registerGhostElement(el: HTMLDivElement | null) {
    ghostEl = el;
    if (el) {
        positionGhost(lastPointer.x, lastPointer.y);
    }
}

export function getLastPointer() {
    return lastPointer;
}

export function startFieldDrag(
    event: React.PointerEvent<HTMLElement>,
    field: DraggedField,
) {
    if (event.button !== 0) return;

    const startX = event.clientX;
    const startY = event.clientY;
    lastPointer = {x: startX, y: startY};
    let started = false;

    const cleanup = () => {
        window.removeEventListener('pointermove', onMove, true);
        window.removeEventListener('pointerup', onUp, true);
        window.removeEventListener('pointercancel', onCancel, true);
        window.removeEventListener('keydown', onKeyDown, true);
        document.documentElement.classList.remove('tameshk-field-dragging');
        setSnapshot({activeField: null, hoveredDropId: null});
    };

    const onKeyDown = (e: KeyboardEvent) => {
        if (e.key === 'Escape') {
            e.preventDefault();
            cleanup();
        }
    };

    const onMove = (e: PointerEvent) => {
        lastPointer = {x: e.clientX, y: e.clientY};

        if (!started) {
            if (Math.hypot(e.clientX - startX, e.clientY - startY) < DRAG_THRESHOLD_PX) {
                return;
            }

            started = true;
            window.getSelection()?.removeAllRanges();
            document.documentElement.classList.add('tameshk-field-dragging');
            const hoveredDropId = findDropIdAt(e.clientX, e.clientY);
            setSnapshot({activeField: field, hoveredDropId});
            window.addEventListener('keydown', onKeyDown, true);
        }

        positionGhost(e.clientX, e.clientY);
        const hoveredDropId = findDropIdAt(e.clientX, e.clientY);
        if (hoveredDropId !== snapshot.hoveredDropId) {
            setSnapshot({activeField: field, hoveredDropId});
        }
    };

    const onUp = (e: PointerEvent) => {
        if (started) {
            const dropId = findDropIdAt(e.clientX, e.clientY);
            const handler = dropId ? dropHandlers.get(dropId) : undefined;
            cleanup();
            if (handler) {
                handler(field);
            }
            return;
        }
        cleanup();
    };

    const onCancel = () => {
        cleanup();
    };

    window.addEventListener('pointermove', onMove, true);
    window.addEventListener('pointerup', onUp, true);
    window.addEventListener('pointercancel', onCancel, true);
}

const subscribe = (listener: () => void) => {
    listeners.add(listener);
    return () => {
        listeners.delete(listener);
    };
};

const getSnapshot = () => snapshot;

export function useFieldDragState(): DragSnapshot {
    return useSyncExternalStore(subscribe, getSnapshot, getSnapshot);
}

export function useFieldDrop(onDrop: DropHandler) {
    const id = useId();
    const state = useFieldDragState();
    const handlerRef = useRef<DropHandler>(onDrop);
    handlerRef.current = onDrop;

    useEffect(() => {
        const invoke: DropHandler = (field) => handlerRef.current(field);
        dropHandlers.set(id, invoke);
        return () => {
            dropHandlers.delete(id);
        };
    }, [id]);

    const isDragging = state.activeField !== null;
    const isOver = isDragging && state.hoveredDropId === id;

    return {
        isDragging,
        isOver,
        activeField: state.activeField,
        dropProps: {
            'data-field-drop-id': id,
        },
    };
}

const getActiveDragPath = () => snapshot.activeField?.path ?? null;

export function useActiveDragPath(): string | null {
    return useSyncExternalStore(subscribe, getActiveDragPath, getActiveDragPath);
}
