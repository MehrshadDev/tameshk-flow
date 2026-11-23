import type { Panel } from '@canvas/store/types';
import type { Toast } from '@canvas/types/graph';
import type { Slice } from './types';

export type UiSlice = {
    panel: Panel;
    exitPromptOpen: boolean;
    isFormOpen: boolean;
    toasts: Toast[];
    openPicker: () => void;
    openNode: (id: string) => void;
    closePanel: () => void;
    openExitPrompt: () => void;
    closeExitPrompt: () => void;
    toggleForm: () => void;
    setFormOpen: (open: boolean) => void;
    addToast: (message: string, type?: 'error' | 'success' | 'info', duration?: number) => void;
    removeToast: (id: string) => void;
};

export const createUiSlice: Slice<UiSlice> = (set) => ({
    panel: null,
    exitPromptOpen: false,
    isFormOpen: false,
    toasts: [],

    openPicker: () => set({ panel: { type: 'picker' } }),

    openNode: (id) => set({ panel: { type: 'node', id } }),

    closePanel: () => set({ panel: null, pendingConnection: null, pendingTargetConnection: null }),

    openExitPrompt: () => set({ exitPromptOpen: true }),

    closeExitPrompt: () => set({ exitPromptOpen: false }),

    toggleForm: () => set((state) => ({ isFormOpen: !state.isFormOpen })),

    setFormOpen: (open: boolean) => set({ isFormOpen: open }),

    addToast: (message, type = 'error', duration = 20000) => {
        const id = crypto.randomUUID();
        set((state) => ({
            toasts: [...state.toasts, { id, type, message, duration }],
        }));
    },

    removeToast: (id) => {
        set((state) => ({
            toasts: state.toasts.filter((t) => t.id !== id),
        }));
    },
});
