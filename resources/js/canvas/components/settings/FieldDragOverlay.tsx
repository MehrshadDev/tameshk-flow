import { createPortal } from 'react-dom';
import { getLastPointer, registerGhostElement, useFieldDragState } from '@canvas/lib/fieldDrag';

export function FieldDragOverlay() {
    const { activeField } = useFieldDragState();

    if (!activeField) {
        return null;
    }

    const pt = getLastPointer();

    return createPortal(
        <>
            <style>{`
                html.tameshk-field-dragging,
                html.tameshk-field-dragging * {
                    cursor: grabbing !important;
                    user-select: none !important;
                }
            `}</style>
            <div
                ref={registerGhostElement}
                dir="ltr"
                style={{
                    transform: `translate3d(${pt.x + 12}px, ${pt.y - 16}px, 0)`,
                    willChange: 'transform',
                }}
                className="pointer-events-none fixed left-0 top-0 z-[9999] flex items-center gap-1.5 rounded-lg border border-primary/50 bg-surface-raised px-2.5 py-1 font-mono text-xs font-bold text-primary shadow-xl ring-2 ring-primary/15"
            >
                <span className="size-1.5 rounded-full bg-primary" />
                <span>{activeField.keyName}</span>
            </div>
        </>,
        document.body,
    );
}
