import { useEffect, useRef, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { asString, textDir } from '@canvas/lib/fields';
import { useFieldDrop } from '@canvas/lib/fieldDrag';

export type FieldType = 'string' | 'number' | 'boolean' | 'array' | 'object';

export type SetFieldItem = {
    id: string;
    name: string;
    type: FieldType;
    value: string;
};

type SetFieldsEditorProps = {
    value: unknown;
    onChange: (value: Omit<SetFieldItem, 'id'>[]) => void;
};

const FIELD_TYPES: { value: FieldType; label: string; badge: string }[] = [
    { value: 'string', label: 'String', badge: 'T' },
    { value: 'number', label: 'Number', badge: '#' },
    { value: 'boolean', label: 'Boolean', badge: '✓' },
    { value: 'array', label: 'Array', badge: '[]' },
    { value: 'object', label: 'Object', badge: '{}' },
];

const toItems = (value: unknown): SetFieldItem[] => {
    if (Array.isArray(value)) {
        return value.map((item) => ({
            id: crypto.randomUUID(),
            name: asString(item?.name),
            type: (FIELD_TYPES.some((t) => t.value === item?.type) ? item.type : 'string') as FieldType,
            value: asString(item?.value),
        }));
    }

    if (value && typeof value === 'object') {
        return Object.entries(value)
            .filter(([k, v]) => k !== '' || asString(v) !== '')
            .map(([key, val]) => ({
                id: crypto.randomUUID(),
                name: key,
                type: 'string' as FieldType,
                value: asString(val),
            }));
    }

    return [];
};

const serialize = (items: SetFieldItem[]): Omit<SetFieldItem, 'id'>[] =>
    items.map(({ name, type, value }) => ({ name, type, value }));

const inferKeyFromExpression = (expr: string): string => {
    const match = expr.match(/\{\{\s*(.+?)\s*}}/);
    if (!match) return 'field';

    const inner = match[1].trim();
    const parts = inner.split('.');
    const last = parts[parts.length - 1] ?? 'field';

    return last.replace(/\[\d+]/g, '').replace(/[^a-zA-Z0-9_]/g, '') || 'field';
};

function TypeDropdown({
                          value,
                          onChange,
                      }: {
    value: FieldType;
    onChange: (next: FieldType) => void;
}) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    const current = FIELD_TYPES.find((t) => t.value === value) ?? FIELD_TYPES[0];

    useEffect(() => {
        if (!open) return;

        const handleOutside = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handleOutside);
        return () => document.removeEventListener('mousedown', handleOutside);
    }, [open]);

    return (
        <div ref={ref} className="relative w-36 shrink-0" dir="ltr">
            <button
                type="button"
                onClick={() => setOpen((prev) => !prev)}
                className="flex h-9.5 w-full items-center justify-between gap-2 border-l border-line-border bg-surface-card/60 px-3 text-xs font-medium text-content-main transition hover:bg-surface-card focus:outline-none"
            >
                <span className="flex items-center gap-2">
                    <span className="inline-flex w-4 justify-center font-mono text-[11px] font-bold text-content-muted">
                        {current.badge}
                    </span>
                    <span>{current.label}</span>
                </span>
                <Icon
                    name="chevron-down"
                    className={`size-3.5 text-content-muted transition-transform ${open ? 'rotate-180 text-primary' : ''}`}
                />
            </button>

            {open && (
                <div className="absolute right-0 top-full z-40 mt-1 w-44 overflow-hidden rounded-xl border border-line-border bg-surface-raised p-1 shadow-2xl animate-in fade-in zoom-in-95 duration-100">
                    {FIELD_TYPES.map((option) => {
                        const active = option.value === value;
                        return (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => {
                                    onChange(option.value);
                                    setOpen(false);
                                }}
                                className={`flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-xs transition ${
                                    active
                                        ? 'bg-primary/10 font-bold text-primary'
                                        : 'text-content-main hover:bg-line-border/60'
                                }`}
                            >
                                <span className="flex items-center gap-2.5">
                                    <span
                                        className={`inline-flex w-4 justify-center font-mono text-[11px] font-bold ${
                                            active ? 'text-primary' : 'text-content-muted'
                                        }`}
                                    >
                                        {option.badge}
                                    </span>
                                    <span>{option.label}</span>
                                </span>
                                {active && <Icon name="check" className="size-3.5 text-primary" />}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

function SetFieldRow({
                         item,
                         onUpdate,
                         onRemove,
                     }: {
    item: SetFieldItem;
    onUpdate: (patch: Partial<SetFieldItem>) => void;
    onRemove: () => void;
}) {
    const valueInputRef = useRef<HTMLInputElement | null>(null);
    const isExpr = /\{\{.+?}}/.test(item.value);
    const isBoolFixed = item.type === 'boolean' && !isExpr;

    const nameDrop = useFieldDrop((dragged) => {
        const inferred = inferKeyFromExpression(dragged.expression);
        onUpdate({
            name: item.name.trim() === '' ? inferred : item.name,
            value: item.value.trim() === '' ? dragged.expression : item.value,
        });
    });

    const valueDrop = useFieldDrop((dragged) => {
        const el = valueInputRef.current;
        let nextVal = item.value === '' ? dragged.expression : item.value + dragged.expression;

        if (el && document.activeElement === el) {
            const start = el.selectionStart ?? el.value.length;
            const end = el.selectionEnd ?? el.value.length;
            nextVal = item.value.substring(0, start) + dragged.expression + item.value.substring(end);
        }

        const nextName = item.name.trim() === '' ? inferKeyFromExpression(dragged.expression) : item.name;
        onUpdate({ name: nextName, value: nextVal });
    });

    const valuePlaceholder =
        item.type === 'number'
            ? '0'
            : item.type === 'array'
                ? '["item1", "item2"]'
                : item.type === 'object'
                    ? '{"key": "value"}'
                    : 'value';

    return (
        <div className="group flex items-start gap-2" dir="ltr">
            <button
                type="button"
                aria-label="حذف فیلد"
                onClick={onRemove}
                className="mt-1 flex size-7 shrink-0 items-center justify-center rounded-lg text-content-muted opacity-60 transition hover:bg-primary/10 hover:text-primary group-hover:opacity-100"
            >
                <Icon name="trash" className="size-3.5" />
            </button>

            <div className="min-w-0 flex-1 rounded-xl border border-line-border bg-field shadow-2xs transition focus-within:border-primary/60">
                <div className="flex items-center border-b border-line-border">
                    <input
                        {...nameDrop.dropProps}
                        type="text"
                        dir="ltr"
                        value={item.name}
                        maxLength={200}
                        placeholder="name"
                        onChange={(e) => onUpdate({ name: e.target.value })}
                        className={`h-9.5 min-w-0 flex-1 rounded-tl-xl bg-transparent px-3 font-mono text-xs text-content-main placeholder:text-content-muted/50 focus:outline-none transition ${
                            nameDrop.isOver
                                ? 'bg-primary/10 ring-2 ring-inset ring-primary/40'
                                : nameDrop.isDragging
                                    ? 'bg-primary/3'
                                    : ''
                        }`}
                    />

                    <TypeDropdown
                        value={item.type}
                        onChange={(nextType) => onUpdate({ type: nextType })}
                    />
                </div>

                <div
                    {...valueDrop.dropProps}
                    className={`relative flex items-center rounded-b-xl transition ${
                        valueDrop.isOver
                            ? 'bg-primary/10 ring-2 ring-inset ring-primary/40'
                            : valueDrop.isDragging
                                ? 'bg-primary/3 ring-1 ring-inset ring-primary/35'
                                : ''
                    }`}
                >
                    {isExpr && (
                        <span className="pointer-events-none pl-3 font-mono text-[10px] font-black italic text-emerald-500">
                            fx
                        </span>
                    )}

                    {isBoolFixed ? (
                        <div className="flex h-9.5 w-full items-center justify-between px-3">
                            <button
                                type="button"
                                role="switch"
                                aria-checked={item.value === 'true'}
                                onClick={() =>
                                    onUpdate({
                                        value: item.value === 'true' ? 'false' : 'true',
                                    })
                                }
                                className="flex items-center gap-2.5 focus:outline-none"
                            >
                                <span
                                    className={`relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors ${
                                        item.value === 'true' ? 'bg-emerald-500' : 'bg-line-border'
                                    }`}
                                >
                                    <span
                                        className={`inline-block size-3.5 transform rounded-full bg-white shadow-xs transition-transform ${
                                            item.value === 'true' ? 'translate-x-4.5' : 'translate-x-0.5'
                                        }`}
                                    />
                                </span>
                                <span className="font-mono text-xs text-content-main">
                                    {item.value === 'true' ? 'true' : 'false'}
                                </span>
                            </button>

                            <button
                                type="button"
                                onClick={() => onUpdate({ value: '{{ }}' })}
                                className="rounded px-1.5 py-0.5 font-mono text-[10px] text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                            >
                                fx
                            </button>
                        </div>
                    ) : (
                        <input
                            ref={valueInputRef}
                            type="text"
                            dir={
                                isExpr || item.type !== 'string'
                                    ? 'ltr'
                                    : textDir(item.value, 'ltr')
                            }
                            value={item.value}
                            maxLength={5000}
                            placeholder={valuePlaceholder}
                            onChange={(e) => onUpdate({ value: e.target.value })}
                            className={`h-9.5 w-full rounded-b-xl bg-transparent px-3 text-xs text-content-main placeholder:text-content-muted/50 focus:outline-none ${
                                isExpr
                                    ? 'bg-emerald-500/10 font-mono text-emerald-600 dark:text-emerald-400'
                                    : item.type !== 'string'
                                        ? 'font-mono'
                                        : ''
                            }`}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}

export function SetFieldsEditor({ value, onChange }: SetFieldsEditorProps) {
    const [items, setItems] = useState<SetFieldItem[]>(() => toItems(value));

    const commit = (next: SetFieldItem[]) => {
        setItems(next);
        onChange(serialize(next));
    };

    const addField = (initial?: Partial<Omit<SetFieldItem, 'id'>>) => {
        const next: SetFieldItem[] = [
            ...items,
            {
                id: crypto.randomUUID(),
                name: initial?.name ?? '',
                type: initial?.type ?? 'string',
                value: initial?.value ?? '',
            },
        ];
        commit(next);
    };

    const update = (id: string, patch: Partial<SetFieldItem>) => {
        commit(
            items.map((item) => {
                if (item.id !== id) return item;
                const updated = { ...item, ...patch };
                if (patch.type === 'boolean' && item.type !== 'boolean' && !/\{\{.+?}}/.test(updated.value)) {
                    if (updated.value !== 'true' && updated.value !== 'false') {
                        updated.value = 'false';
                    }
                }
                return updated;
            }),
        );
    };

    const remove = (id: string) => {
        commit(items.filter((item) => item.id !== id));
    };

    const zoneDrop = useFieldDrop((dragged) => {
        const validType: FieldType = FIELD_TYPES.some((t) => t.value === dragged.fieldType)
            ? dragged.fieldType
            : 'string';

        const baseKey = inferKeyFromExpression(dragged.expression);
        const existingNames = new Set(items.map((i) => i.name.trim()));
        let uniqueName = baseKey;
        let counter = 1;
        while (existingNames.has(uniqueName)) {
            uniqueName = `${baseKey}_${counter++}`;
        }

        addField({
            name: uniqueName,
            type: validType,
            value: dragged.expression,
        });
    });

    return (
        <div className="space-y-3">
            {items.map((item) => (
                <SetFieldRow
                    key={item.id}
                    item={item}
                    onUpdate={(patch) => update(item.id, patch)}
                    onRemove={() => remove(item.id)}
                />
            ))}

            <div
                {...zoneDrop.dropProps}
                className={`flex flex-col items-center justify-center rounded-xl border border-dashed px-4 py-5 text-center transition ${
                    zoneDrop.isOver
                        ? 'border-primary bg-primary/10 ring-2 ring-primary/20'
                        : zoneDrop.isDragging
                            ? 'border-primary/60 bg-primary/4'
                            : 'border-line-border bg-surface-card/30 hover:border-content-muted/40'
                }`}
            >
                {zoneDrop.isDragging && zoneDrop.activeField ? (
                    <div className="flex flex-wrap items-center justify-center gap-1.5 text-xs font-semibold text-content-main">
                        <span>برای افزودن فیلد</span>
                        <span
                            dir="ltr"
                            className="rounded-md bg-primary/12 px-1.5 py-0.5 font-mono font-bold text-primary"
                        >
                            {zoneDrop.activeField.keyName}
                        </span>
                        <span>اینجا رها کنید</span>
                    </div>
                ) : (
                    <div className="flex flex-wrap items-center justify-center gap-1.5 text-xs">
                        <span className="text-content-muted">فیلدهای ورودی را به اینجا بکشید یا</span>
                        <button
                            type="button"
                            onClick={() => addField()}
                            className="font-bold text-primary transition hover:underline"
                        >
                            افزودن فیلد (Add Field)
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
