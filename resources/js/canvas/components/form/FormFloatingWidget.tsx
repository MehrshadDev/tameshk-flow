import { useMemo, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { Select } from '@canvas/components/ui/Select';
import { Tooltip } from '@canvas/components/ui/Tooltip';
import { asString, textDir } from '@canvas/lib/fields';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

type NormalizedFormField = {
    name: string;
    label: string;
    type: 'text' | 'email' | 'tel' | 'number' | 'textarea' | 'select';
    placeholder: string;
    required: boolean;
    options: string[];
};

const DEFAULT_FIELDS: NormalizedFormField[] = [
    {
        name: 'name',
        label: 'نام و نام خانوادگی',
        type: 'text',
        placeholder: 'مثلاً: علی محمدی',
        required: true,
        options: [],
    },
    {
        name: 'email',
        label: 'آدرس ایمیل',
        type: 'email',
        placeholder: 'example@gmail.com',
        required: true,
        options: [],
    },
    {
        name: 'message',
        label: 'متن پیام یا درخواست',
        type: 'textarea',
        placeholder: 'پیام خود را اینجا بنویسید...',
        required: true,
        options: [],
    },
];

const parseFields = (raw: unknown): NormalizedFormField[] => {
    if (!Array.isArray(raw) || raw.length === 0) {
        return DEFAULT_FIELDS;
    }

    const allowed = new Set(['text', 'email', 'tel', 'number', 'textarea', 'select']);
    const used = new Set<string>();
    const result: NormalizedFormField[] = [];

    raw.forEach((item, idx) => {
        if (!item || typeof item !== 'object') return;

        let cleanName = asString(item.name).trim().replace(/[^a-zA-Z0-9_]/g, '_').replace(/^_+|_+$/g, '');
        if (!cleanName) cleanName = `field_${idx + 1}`;
        if (used.has(cleanName)) cleanName = `${cleanName}_${idx + 1}`;
        used.add(cleanName);

        const rawType = asString(item.type);
        const type = (allowed.has(rawType) ? rawType : 'text') as NormalizedFormField['type'];
        const optionsRaw = asString(item.options).trim();
        const options =
            type === 'select' && optionsRaw !== ''
                ? optionsRaw
                    .split(/[,،\r\n]+/)
                    .map((s) => s.trim())
                    .filter(Boolean)
                : [];

        result.push({
            name: cleanName,
            label: asString(item.label).trim() || cleanName,
            type,
            placeholder: asString(item.placeholder).trim(),
            required: item.required !== undefined ? Boolean(item.required) : true,
            options,
        });
    });

    return result.length > 0 ? result : DEFAULT_FIELDS;
};

export function FormFloatingWidget() {
    const nodes = useWorkflowStore((state) => state.nodes);
    const isFormOpen = useWorkflowStore((state) => state.isFormOpen);
    const toggleForm = useWorkflowStore((state) => state.toggleForm);
    const setFormOpen = useWorkflowStore((state) => state.setFormOpen);
    const executeWorkflow = useWorkflowStore((state) => state.executeWorkflow);
    const cancelWorkflow = useWorkflowStore((state) => state.cancelWorkflow);
    const isRunning = useWorkflowStore((state) => state.isRunning);

    const [values, setValues] = useState<Record<string, string>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [resultBanner, setResultBanner] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const formTriggerNode = nodes.find((n) => n.data.nodeType === 'trigger.form');
    const config = formTriggerNode?.data.config ?? {};
    const fields = useMemo(() => parseFields(config.fields), [config.fields]);

    if (!formTriggerNode) {
        return null;
    }

    const title = asString(config.title).trim() || 'فرم تماس با ما';
    const description = asString(config.description).trim();
    const submitLabel = asString(config.submit_label).trim() || 'ارسال پیام';
    const responseMessage =
        asString(config.response_message).trim() || 'پیام شما با موفقیت ثبت شد. از همراهی شما سپاسگزاریم!';

    const setField = (key: string, val: string) => {
        setValues((prev) => ({ ...prev, [key]: val }));
        if (errors[key]) {
            setErrors((prev) => {
                const next = { ...prev };
                delete next[key];
                return next;
            });
        }
    };

    const resetForm = () => {
        setValues({});
        setErrors({});
        setResultBanner(null);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (isRunning) return;

        setResultBanner(null);
        const nextErrors: Record<string, string> = {};
        const payload: Record<string, unknown> = {};

        for (const field of fields) {
            const rawVal = (values[field.name] ?? '').trim();

            if (field.required && rawVal === '') {
                nextErrors[field.name] = `وارد کردن «${field.label}» الزامی است.`;
                continue;
            }

            if (rawVal === '') {
                payload[field.name] = '';
                continue;
            }

            if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(rawVal)) {
                nextErrors[field.name] = 'فرمت ایمیل واردشده معتبر نیست.';
                continue;
            }

            if (field.type === 'number') {
                const num = Number(rawVal);
                if (!Number.isFinite(num)) {
                    nextErrors[field.name] = 'لطفاً یک عدد معتبر وارد کنید.';
                    continue;
                }
                payload[field.name] = num;
                continue;
            }

            payload[field.name] = rawVal;
        }

        if (Object.keys(nextErrors).length > 0) {
            setErrors(nextErrors);
            return;
        }

        setErrors({});
        const prevRunId = useWorkflowStore.getState().lastRun?.id ?? null;

        await executeWorkflow(payload);

        const run = useWorkflowStore.getState().lastRun;
        if (!run || run.id === prevRunId) {
            setResultBanner({ type: 'error', text: 'اجرای ورکفلو تکمیل نشد.' });
            return;
        }

        if (run.status === 'failed') {
            setResultBanner({ type: 'error', text: run.error ?? 'اجرای ورکفلو با خطا مواجه شد.' });
            return;
        }

        if (run.status === 'canceled') {
            setResultBanner({ type: 'error', text: 'اجرای ورکفلو متوقف شد.' });
            return;
        }

        setResultBanner({ type: 'success', text: responseMessage });
    };

    return (
        <div className="fixed bottom-5 left-34 z-20" dir="ltr">
            {isFormOpen && (
                <div
                    dir="rtl"
                    className="absolute bottom-14 left-0 flex max-h-140 w-96 max-w-[calc(100vw-6rem)] flex-col overflow-hidden rounded-3xl border border-line-border bg-surface-raised/95 shadow-2xl backdrop-blur-xl duration-150 animate-in fade-in zoom-in-95"
                >
                    <div className="flex h-16 shrink-0 items-center justify-between border-b border-line-border/70 px-5">
                        <div className="flex min-w-0 items-center gap-3">
                            <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                                <Icon name="clipboard-list" className="size-5" />
                            </div>
                            <div className="min-w-0 space-y-0.5">
                                <span className="block truncate text-xs font-bold leading-tight text-content-main">
                                    {title}
                                </span>
                                <span className="block text-[11px] leading-tight text-content-muted">
                                    تست تعاملی فرم
                                </span>
                            </div>
                        </div>

                        <div className="flex items-center gap-1.5">
                            <button
                                type="button"
                                onClick={resetForm}
                                disabled={isRunning}
                                title="پاک‌کردن مقادیر فرم"
                                className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main disabled:opacity-50"
                            >
                                <Icon name="trash" className="size-4" />
                            </button>

                            <button
                                type="button"
                                onClick={() => setFormOpen(false)}
                                title="بستن"
                                className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                            >
                                <Icon name="x" className="size-4" />
                            </button>
                        </div>
                    </div>

                    <form
                        onSubmit={(e) => void handleSubmit(e)}
                        noValidate
                        className="flex min-h-0 flex-1 flex-col"
                    >
                        <div className="custom-scrollbar flex-1 space-y-3.5 overflow-y-auto p-4 select-text">
                            {description !== '' && (
                                <p className="rounded-2xl border border-line-border/60 bg-surface-card/60 px-3.5 py-2.5 text-[11px] leading-5 text-content-muted">
                                    {description}
                                </p>
                            )}

                            {resultBanner && (
                                <div
                                    className={`flex items-start gap-2 rounded-2xl border p-3 text-xs leading-5 font-medium ${
                                        resultBanner.type === 'success'
                                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                            : 'border-primary/30 bg-primary/10 text-primary'
                                    }`}
                                >
                                    <Icon
                                        name={resultBanner.type === 'success' ? 'check' : 'alert-triangle'}
                                        className="mt-0.5 size-4 shrink-0"
                                    />
                                    <span className="flex-1">{resultBanner.text}</span>
                                </div>
                            )}

                            {fields.map((field) => {
                                const val = values[field.name] ?? '';
                                const err = errors[field.name];
                                const isLtrType =
                                    field.type === 'email' || field.type === 'tel' || field.type === 'number';
                                const resolvedDir = isLtrType
                                    ? 'ltr'
                                    : textDir(val, field.placeholder || field.label);
                                const isLtr = resolvedDir === 'ltr';

                                return (
                                    <div key={field.name} className="space-y-1">
                                        <label className="flex items-center gap-1 text-xs font-bold text-content-main">
                                            <span>{field.label}</span>
                                            {field.required && <span className="text-primary">*</span>}
                                        </label>

                                        {field.type === 'textarea' ? (
                                            <textarea
                                                rows={3}
                                                dir={resolvedDir}
                                                disabled={isRunning}
                                                value={val}
                                                placeholder={field.placeholder}
                                                onChange={(e) => setField(field.name, e.target.value)}
                                                className={`custom-scrollbar w-full resize-none rounded-xl border bg-surface-card px-3.5 py-2.5 text-xs leading-5 text-content-main transition placeholder:text-content-muted/55 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:opacity-50 ${
                                                    isLtr ? 'text-left' : 'text-right placeholder:text-right'
                                                } ${err ? 'border-primary' : 'border-line-border'}`}
                                            />
                                        ) : field.type === 'select' ? (
                                            <Select
                                                value={val}
                                                searchable={false}
                                                placeholder={field.placeholder || 'انتخاب کنید...'}
                                                options={field.options.map((opt) => ({
                                                    value: opt,
                                                    label: opt,
                                                }))}
                                                onChange={(e) => setField(field.name, e.target.value)}
                                            />
                                        ) : (
                                            <input
                                                type={field.type === 'number' ? 'text' : field.type}
                                                inputMode={
                                                    field.type === 'number'
                                                        ? 'decimal'
                                                        : field.type === 'tel'
                                                            ? 'tel'
                                                            : undefined
                                                }
                                                dir={resolvedDir}
                                                disabled={isRunning}
                                                value={val}
                                                placeholder={field.placeholder}
                                                onChange={(e) => setField(field.name, e.target.value)}
                                                className={`h-9.5 w-full rounded-xl border bg-surface-card px-3.5 text-xs text-content-main transition placeholder:text-content-muted/55 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:opacity-50 ${
                                                    isLtrType
                                                        ? 'text-left font-mono'
                                                        : isLtr
                                                            ? 'text-left'
                                                            : 'text-right placeholder:text-right'
                                                } ${err ? 'border-primary' : 'border-line-border'}`}
                                            />
                                        )}

                                        {err && <p className="text-[11px] font-medium text-primary">{err}</p>}
                                    </div>
                                );
                            })}
                        </div>

                        <div className="border-t border-line-border/70 bg-surface-card/40 p-3">
                            {isRunning ? (
                                <button
                                    type="button"
                                    onClick={() => void cancelWorkflow()}
                                    className="flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 text-xs font-bold text-white shadow-xs transition hover:bg-amber-600"
                                >
                                    <Icon name="player-stop" className="size-4" />
                                    <span>توقف اجرای فرم</span>
                                </button>
                            ) : (
                                <button
                                    type="submit"
                                    className="flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 text-xs font-bold text-white shadow-xs transition hover:bg-primary/90"
                                >
                                    <span>{submitLabel}</span>
                                    <Icon name="send" className="size-3.5 -scale-x-100" />
                                </button>
                            )}
                        </div>
                    </form>
                </div>
            )}

            <Tooltip label={isFormOpen ? 'بستن تست فرم' : 'تست فرم ورکفلو'}>
                <button
                    type="button"
                    onClick={toggleForm}
                    className={`relative flex size-11 items-center justify-center rounded-2xl border shadow-2xl backdrop-blur-md transition-all active:scale-95 ${
                        isFormOpen
                            ? 'border-line-border bg-surface-raised text-content-main hover:bg-surface-card'
                            : 'border-emerald-500/40 bg-emerald-500 text-white shadow-emerald-500/20 hover:bg-emerald-600'
                    }`}
                >
                    <Icon name={isFormOpen ? 'x' : 'clipboard-list'} className="size-5" />
                </button>
            </Tooltip>
        </div>
    );
}
