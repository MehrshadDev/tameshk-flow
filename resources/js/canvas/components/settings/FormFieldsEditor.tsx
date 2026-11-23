import { useState } from 'react';
import { moveArrayItem } from '@canvas/components/settings/ConditionOperatorSelect';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { Icon } from '@canvas/components/ui/Icon';
import { Select } from '@canvas/components/ui/Select';
import { asString } from '@canvas/lib/fields';

export type FormFieldType = 'text' | 'email' | 'tel' | 'number' | 'textarea' | 'select';

export type FormFieldItem = {
    id: string;
    name: string;
    label: string;
    type: FormFieldType;
    placeholder: string;
    required: boolean;
    options: string;
};

type SerializedFormField = Omit<FormFieldItem, 'id'>;

type FormFieldsEditorProps = {
    value: unknown;
    onChange: (value: SerializedFormField[]) => void;
};

const FIELD_TYPE_OPTIONS: { value: FormFieldType; label: string }[] = [
    { value: 'text', label: 'متن کوتاه (Text)' },
    { value: 'email', label: 'ایمیل (Email)' },
    { value: 'tel', label: 'شماره تماس (Phone)' },
    { value: 'number', label: 'عدد (Number)' },
    { value: 'textarea', label: 'متن چندخطی (Textarea)' },
    { value: 'select', label: 'لیست کشویی (Dropdown)' },
];

const DEFAULT_FIELDS: SerializedFormField[] = [
    {
        name: 'name',
        label: 'نام و نام خانوادگی',
        type: 'text',
        placeholder: 'مثلاً: علی محمدی',
        required: true,
        options: '',
    },
    {
        name: 'email',
        label: 'آدرس ایمیل',
        type: 'email',
        placeholder: 'example@gmail.com',
        required: true,
        options: '',
    },
    {
        name: 'message',
        label: 'متن پیام یا درخواست',
        type: 'textarea',
        placeholder: 'پیام خود را اینجا بنویسید...',
        required: true,
        options: '',
    },
];

const toItems = (raw: unknown): FormFieldItem[] => {
    const list = Array.isArray(raw) && raw.length > 0 ? raw : DEFAULT_FIELDS;

    return list.map((item, idx) => {
        const rawType = asString(item?.type) as FormFieldType;
        const validType = FIELD_TYPE_OPTIONS.some((t) => t.value === rawType) ? rawType : 'text';

        return {
            id: crypto.randomUUID(),
            name: asString(item?.name) || `field_${idx + 1}`,
            label: asString(item?.label) || `فیلد ${idx + 1}`,
            type: validType,
            placeholder: asString(item?.placeholder),
            required: item?.required !== undefined ? Boolean(item.required) : true,
            options: asString(item?.options),
        };
    });
};

const serialize = (items: FormFieldItem[]): SerializedFormField[] =>
    items.map(({ name, label, type, placeholder, required, options }) => ({
        name,
        label,
        type,
        placeholder,
        required,
        options,
    }));

const nextFieldName = (items: FormFieldItem[]): string => {
    const used = new Set(items.map((i) => i.name.trim()));
    let n = items.length + 1;
    while (used.has(`field_${n}`)) {
        n++;
    }
    return `field_${n}`;
};

export function FormFieldsEditor({ value, onChange }: FormFieldsEditorProps) {
    const [items, setItems] = useState<FormFieldItem[]>(() => toItems(value));

    const commit = (next: FormFieldItem[]) => {
        setItems(next);
        onChange(serialize(next));
    };

    const update = (id: string, patch: Partial<FormFieldItem>) => {
        commit(items.map((item) => (item.id === id ? { ...item, ...patch } : item)));
    };

    const addField = () => {
        if (items.length >= 25) return;

        const key = nextFieldName(items);
        commit([
            ...items,
            {
                id: crypto.randomUUID(),
                name: key,
                label: `فیلد ${items.length + 1}`,
                type: 'text',
                placeholder: '',
                required: false,
                options: '',
            },
        ]);
    };

    const removeField = (id: string) => {
        if (items.length <= 1) return;
        commit(items.filter((item) => item.id !== id));
    };

    const moveField = (fromIndex: number, toIndex: number) => {
        const reordered = moveArrayItem(items, fromIndex, toIndex);
        if (reordered) commit(reordered);
    };

    return (
        <div className="space-y-3">
            {items.map((item, idx) => {
                const cleanVar = item.name.trim().replace(/[^a-zA-Z0-9_]/g, '_') || `field_${idx + 1}`;

                return (
                    <div
                        key={item.id}
                        className="rounded-2xl border border-line-border/90 bg-surface-card p-3.5 shadow-2xs transition hover:border-line-border"
                    >
                        <div className="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-line-border/60 pb-2.5">
                            <div className="flex min-w-0 items-center gap-2">
                                <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 font-mono text-[10px] font-bold text-primary">
                                    {idx + 1}
                                </span>
                                <span className="truncate text-xs font-bold text-content-main">
                                    {item.label || `فیلد ${idx + 1}`}
                                </span>
                                <span
                                    dir="ltr"
                                    title="متغیر خروجی این فیلد در نودهای بعدی"
                                    className="rounded-md border border-emerald-500/25 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {`{{ input.${cleanVar} }}`}
                                </span>
                            </div>

                            <div className="flex shrink-0 items-center gap-1.5">
                                <button
                                    type="button"
                                    onClick={() => update(item.id, { required: !item.required })}
                                    className={`rounded-lg border px-2 py-0.5 text-[10px] font-bold transition ${
                                        item.required
                                            ? 'border-primary/30 bg-primary/10 text-primary'
                                            : 'border-line-border bg-surface-base text-content-muted hover:text-content-main'
                                    }`}
                                >
                                    {item.required ? 'الزامی *' : 'اختیاری'}
                                </button>

                                {items.length > 1 && (
                                    <>
                                        <button
                                            type="button"
                                            disabled={idx === 0}
                                            onClick={() => moveField(idx, idx - 1)}
                                            title="انتقال به بالا"
                                            className="flex size-6 items-center justify-center rounded-md text-content-muted transition hover:bg-line-border/50 hover:text-content-main disabled:opacity-25"
                                        >
                                            <Icon name="chevron-down" className="size-3.5 rotate-180" />
                                        </button>
                                        <button
                                            type="button"
                                            disabled={idx === items.length - 1}
                                            onClick={() => moveField(idx, idx + 1)}
                                            title="انتقال به پایین"
                                            className="flex size-6 items-center justify-center rounded-md text-content-muted transition hover:bg-line-border/50 hover:text-content-main disabled:opacity-25"
                                        >
                                            <Icon name="chevron-down" className="size-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => removeField(item.id)}
                                            title="حذف فیلد"
                                            className="flex size-6 items-center justify-center rounded-md text-content-muted transition hover:bg-primary/10 hover:text-primary"
                                        >
                                            <Icon name="trash" className="size-3.5" />
                                        </button>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div className="space-y-1">
                                <label className="block text-[11px] font-semibold text-content-muted">
                                    عنوان نمایشی فیلد
                                </label>
                                <input
                                    type="text"
                                    dir="auto"
                                    maxLength={100}
                                    value={item.label}
                                    placeholder="مثلاً: نام و نام خانوادگی"
                                    onChange={(e) => update(item.id, { label: e.target.value })}
                                    className={`${inputClass} py-2! text-xs`}
                                />
                            </div>

                            <div className="space-y-1">
                                <label className="block text-[11px] font-semibold text-content-muted">
                                    کلید متغیر (انگلیسی)
                                </label>
                                <input
                                    type="text"
                                    dir="ltr"
                                    maxLength={60}
                                    value={item.name}
                                    placeholder="name"
                                    onChange={(e) =>
                                        update(item.id, {
                                            name: e.target.value.replace(/[^a-zA-Z0-9_]/g, '_'),
                                        })
                                    }
                                    className={`${inputClass} py-2! text-left font-mono text-xs`}
                                />
                            </div>

                            <div className="space-y-1">
                                <label className="block text-[11px] font-semibold text-content-muted">
                                    نوع فیلد
                                </label>
                                <Select
                                    value={item.type}
                                    searchable={false}
                                    options={FIELD_TYPE_OPTIONS}
                                    onChange={(e) =>
                                        update(item.id, { type: e.target.value as FormFieldType })
                                    }
                                />
                            </div>

                            <div className="space-y-1">
                                <label className="block text-[11px] font-semibold text-content-muted">
                                    متن راهنمای داخل کادر (Placeholder)
                                </label>
                                <input
                                    type="text"
                                    dir="auto"
                                    maxLength={150}
                                    value={item.placeholder}
                                    placeholder="اختیاری..."
                                    onChange={(e) => update(item.id, { placeholder: e.target.value })}
                                    className={`${inputClass} py-2! text-xs`}
                                />
                            </div>
                        </div>

                        {item.type === 'select' && (
                            <div className="mt-3 space-y-1 border-t border-line-border/50 pt-2.5">
                                <label className="block text-[11px] font-semibold text-content-muted">
                                    گزینه‌های لیست کشویی (با کاما جدا کنید)
                                </label>
                                <input
                                    type="text"
                                    dir="auto"
                                    maxLength={1000}
                                    value={item.options}
                                    placeholder="مثلاً: فروش، پشتیبانی فنی، امور مالی"
                                    onChange={(e) => update(item.id, { options: e.target.value })}
                                    className={`${inputClass} py-2! text-xs`}
                                />
                            </div>
                        )}
                    </div>
                );
            })}

            {items.length < 25 && (
                <button
                    type="button"
                    onClick={addField}
                    className="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-line-border bg-surface-card/50 py-2.5 text-xs font-bold text-content-muted transition hover:border-primary hover:bg-primary/5 hover:text-primary active:scale-[0.99]"
                >
                    <Icon name="plus" className="size-3.5" />
                    <span>افزودن فیلد جدید به فرم</span>
                </button>
            )}
        </div>
    );
}
