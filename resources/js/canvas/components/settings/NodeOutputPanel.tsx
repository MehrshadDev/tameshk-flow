import type { RefObject } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { hasRtlChars } from '@canvas/lib/fields';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import type { NodeRunData } from '@canvas/types/graph';

type NodeOutputPanelProps = {
    panelRef?: RefObject<HTMLElement | null>;
    width: number;
    visibleOnMobile: boolean;
    isManualTrigger: boolean;
    currentNodeRun?: NodeRunData;
};

export function NodeOutputPanel({
                                    panelRef,
                                    width,
                                    visibleOnMobile,
                                    isManualTrigger,
                                    currentNodeRun,
                                }: NodeOutputPanelProps) {
    const addToast = useWorkflowStore((state) => state.addToast);

    const hasOutputData =
        currentNodeRun !== undefined &&
        currentNodeRun.output_data !== null &&
        currentNodeRun.output_data !== undefined;

    const showOutputBlock =
        currentNodeRun !== undefined && (hasOutputData || !currentNodeRun.error);

    const isPersianError = Boolean(currentNodeRun?.error && hasRtlChars(currentNodeRun.error));

    const copyOutputJson = () => {
        if (!hasOutputData) return;
        navigator.clipboard.writeText(JSON.stringify(currentNodeRun?.output_data, null, 2));
        addToast('داده‌های خروجی کپی شدند.', 'success', 2500);
    };

    const copyErrorText = () => {
        if (!currentNodeRun?.error) return;
        navigator.clipboard.writeText(currentNodeRun.error);
        addToast('متن خطا در کلیپ‌بورد کپی شد.', 'success', 2000);
    };

    return (
        <section
            ref={panelRef}
            style={{ width: window.innerWidth >= 768 ? width : '100%' }}
            className={`min-h-0 shrink-0 flex-col bg-surface-base/80 dark:bg-surface-base/45 ${
                visibleOnMobile ? 'flex' : 'hidden md:flex'
            }`}
            dir="rtl"
        >
            <div className="flex h-11 shrink-0 items-center justify-between gap-2 border-b border-line-border bg-surface-raised/70 px-4">
                <span className="flex shrink-0 items-center gap-2 whitespace-nowrap text-xs font-bold text-content-main">
                    <Icon name="sparkles" className="size-4 shrink-0 text-primary" />
                    <span>نتیجه خروجی</span>
                </span>

                {currentNodeRun && (
                    <div className="flex shrink-0 items-center gap-2">
                        {hasOutputData && (
                            <button
                                type="button"
                                onClick={copyOutputJson}
                                title="کپی داده‌های خروجی"
                                className="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-line-border bg-surface-card px-2 py-1 text-[11px] font-medium text-content-muted transition hover:border-primary/40 hover:text-content-main"
                            >
                                <Icon name="copy" className="size-3 shrink-0" />
                                <span>کپی</span>
                            </button>
                        )}

                        {currentNodeRun.duration_ms !== null && (
                            <span
                                className="inline-flex items-center gap-1 whitespace-nowrap font-mono text-[11px] text-content-muted"
                                dir="ltr"
                            >
                                <Icon name="clock" className="size-3 shrink-0" />
                                <span>{currentNodeRun.duration_ms} ms</span>
                            </span>
                        )}

                        <span
                            className={`whitespace-nowrap rounded-lg px-2 py-0.5 text-[11px] font-bold ${
                                currentNodeRun.status === 'success'
                                    ? 'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-500'
                                    : 'bg-primary/12 text-primary'
                            }`}
                        >
                            {currentNodeRun.status === 'success' ? 'موفق' : 'خطا'}
                        </span>
                    </div>
                )}
            </div>

            <div className="custom-scrollbar flex-1 space-y-4 overflow-y-auto p-4 select-text sm:p-5">
                {!currentNodeRun ? (
                    <div className="space-y-2 py-16 text-center text-xs text-content-muted">
                        <Icon name="clock" className="mx-auto size-7 opacity-45" />
                        <p>
                            {!isManualTrigger
                                ? 'ورکفلو را از طریق ورودی مربوطه اجرا کنید تا خروجی نمایش داده شود.'
                                : 'این نود هنوز در تست اجرا نشده است.'}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4 text-xs">
                        {currentNodeRun.error && (
                            <div className="max-h-64 space-y-2.5 overflow-y-auto rounded-2xl border border-primary/25 bg-primary/[0.07] p-4 text-xs leading-6 text-primary">
                                <div className="flex items-center justify-between gap-2 border-b border-primary/20 pb-2">
                                    <span className="flex items-center gap-1.5 whitespace-nowrap text-xs font-bold">
                                        <Icon name="alert-triangle" className="size-3.5 shrink-0" />
                                        <span>خطای اجرا</span>
                                    </span>
                                    <button
                                        type="button"
                                        onClick={copyErrorText}
                                        className="inline-flex items-center gap-1 whitespace-nowrap text-[11px] font-semibold text-primary transition hover:underline"
                                    >
                                        <Icon name="copy" className="size-3 shrink-0" />
                                        <span>کپی متن خطا</span>
                                    </button>
                                </div>
                                <p
                                    dir={isPersianError ? 'rtl' : 'ltr'}
                                    className={`select-text whitespace-pre-wrap wrap-break-word leading-6 ${
                                        isPersianError
                                            ? 'text-right text-xs font-medium'
                                            : 'text-left font-mono text-[11px]'
                                    }`}
                                >
                                    {currentNodeRun.error}
                                </p>
                            </div>
                        )}

                        {showOutputBlock && (
                            <div className="space-y-1.5">
                                <pre
                                    dir="ltr"
                                    className="custom-scrollbar max-h-96 overflow-auto rounded-2xl border border-line-border bg-surface-card p-4 font-mono text-[11px] leading-5 text-content-main shadow-2xs select-text"
                                >
                                    {JSON.stringify(currentNodeRun.output_data, null, 2) ?? 'null'}
                                </pre>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}
