import { useRef, useState } from 'react';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { Icon } from '@canvas/components/ui/Icon';
import { asString, textDir } from '@canvas/lib/fields';
import { useFieldDrop } from '@canvas/lib/fieldDrag';

type Row = { id: string; key: string; value: string };

type KeyValueEditorProps = {
    value: unknown;
    onChange: (value: Record<string, string>) => void;
};

const toRows = (value: unknown): Row[] => {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const entries = Object.entries(value).filter(([k, v]) => k !== '' || asString(v) !== '');
        if (entries.length > 0) {
            return entries.map(([key, item]) => ({
                id: crypto.randomUUID(),
                key,
                value: asString(item),
            }));
        }
    }

    return [];
};

const toObject = (rows: Row[]): Record<string, string> =>
    Object.fromEntries(
        rows
            .filter((row) => row.key.trim() !== '')
            .map((row) => [row.key.trim(), row.value]),
    );

const inferKeyFromExpression = (expr: string): string => {
    const match = expr.match(/\{\{\s*(.+?)\s*}}/);
    if (!match) return 'field';

    const inner = match[1].trim();
    const parts = inner.split('.');
    const last = parts[parts.length - 1] ?? 'field';

    return last.replace(/\[\d+]/g, '').replace(/[^a-zA-Z0-9_]/g, '') || 'field';
};

function KeyValueRow({
                         row,
                         index,
                         onUpdate,
                         onRemove,
                     }: {
    row: Row;
    index: number;
    onUpdate: (patch: Partial<Row>) => void;
    onRemove: () => void;
}) {
    const valueInputRef = useRef<HTMLInputElement | null>(null);
    const isExpr = /\{\{.+?}}/.test(row.value);

    const keyDrop = useFieldDrop((dragged) => {
        const inferredKey = inferKeyFromExpression(dragged.expression);
        onUpdate({
            key: row.key.trim() === '' ? inferredKey : row.key,
            value: row.value.trim() === '' ? dragged.expression : row.value,
        });
    });

    const valueDrop = useFieldDrop((dragged) => {
        const el = valueInputRef.current;
        let nextVal = row.value === '' ? dragged.expression : row.value + dragged.expression;

        if (el && document.activeElement === el) {
            const start = el.selectionStart ?? el.value.length;
            const end = el.selectionEnd ?? el.value.length;
            nextVal = row.value.substring(0, start) + dragged.expression + row.value.substring(end);
        }

        const nextKey = row.key.trim() === '' ? inferKeyFromExpression(dragged.expression) : row.key;
        onUpdate({ key: nextKey, value: nextVal });
    });

    return (
        <div className="group relative rounded-2xl border border-line-border/80 bg-surface-card p-3.5 shadow-2xs transition hover:border-line-border">
            <div className="mb-2.5 flex items-center justify-between">
                <span className="text-[11px] font-bold text-content-muted">
                    فیلد #{index + 1}
                </span>
                <button
                    type="button"
                    aria-label="حذف فیلد"
                    onClick={onRemove}
                    className="flex size-7 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                >
                    <Icon name="trash" className="size-3.5" />
                </button>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div className="space-y-1">
                    <label className="block text-[11px] font-medium text-content-muted">
                        نام فیلد (Name)
                    </label>
                    <input
                        {...keyDrop.dropProps}
                        dir="ltr"
                        value={row.key}
                        maxLength={200}
                        placeholder="name"
                        onChange={(event) => onUpdate({ key: event.target.value })}
                        className={`${inputClass} text-left font-mono text-xs ${
                            keyDrop.isOver
                                ? 'border-dashed! border-primary! bg-primary/[0.06]! ring-2! ring-primary/25!'
                                : keyDrop.isDragging
                                    ? 'border-dashed! border-primary/55!'
                                    : ''
                        }`}
                    />
                </div>

                <div className="space-y-1">
                    <div className="flex items-center justify-between">
                        <label className="block text-[11px] font-medium text-content-muted">
                            مقدار (Value)
                        </label>
                        {isExpr && (
                            <span
                                dir="ltr"
                                className="rounded bg-emerald-500/10 px-1.5 py-0.2 font-mono text-[9px] font-bold text-emerald-500"
                            >
                                fx
                            </span>
                        )}
                    </div>
                    <input
                        {...valueDrop.dropProps}
                        ref={valueInputRef}
                        dir={isExpr ? 'ltr' : textDir(row.value, 'rtl')}
                        value={row.value}
                        maxLength={2000}
                        placeholder="مقدار را وارد کنید..."
                        onChange={(event) => onUpdate({ value: event.target.value })}
                        className={`${inputClass} text-xs ${
                            isExpr
                                ? 'border-emerald-500/40! bg-emerald-500/10! text-left font-mono text-emerald-600 dark:text-emerald-400'
                                : ''
                        } ${
                            valueDrop.isOver
                                ? 'border-dashed! border-primary! bg-primary/[0.06]! ring-2! ring-primary/25!'
                                : valueDrop.isDragging
                                    ? 'border-dashed! border-primary/55!'
                                    : ''
                        }`}
                    />
                </div>
            </div>
        </div>
    );
}

export function KeyValueEditor({ value, onChange }: KeyValueEditorProps) {
    const [rows, setRows] = useState<Row[]>(() => toRows(value));

    const commit = (next: Row[]) => {
        setRows(next);
        onChange(toObject(next));
    };

    const addEmptyRow = () => {
        setRows((prev) => [...prev, { id: crypto.randomUUID(), key: '', value: '' }]);
    };

    const update = (id: string, patch: Partial<Row>) =>
        commit(rows.map((row) => (row.id === id ? { ...row, ...patch } : row)));

    const removeRow = (id: string) => {
        commit(rows.filter((item) => item.id !== id));
    };

    const zoneDrop = useFieldDrop((dragged) => {
        const baseKey = inferKeyFromExpression(dragged.expression);
        const existingKeys = new Set(rows.map((r) => r.key.trim()));
        let uniqueKey = baseKey;
        let counter = 1;
        while (existingKeys.has(uniqueKey)) {
            uniqueKey = `${baseKey}_${counter++}`;
        }

        commit([
            ...rows,
            {
                id: crypto.randomUUID(),
                key: uniqueKey,
                value: dragged.expression,
            },
        ]);
    });

    if (rows.length === 0) {
        return (
            <div
                {...zoneDrop.dropProps}
                className={`flex flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 text-center transition ${
                    zoneDrop.isOver
                        ? 'border-primary bg-primary/10 ring-2 ring-primary/20'
                        : zoneDrop.isDragging
                            ? 'border-primary/60 bg-primary/[0.04]'
                            : 'border-line-border bg-surface-card/40 hover:border-content-muted/40'
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
                    <>
                        <p className="text-xs font-medium text-content-muted">
                            فیلدهای ورودی را به اینجا بکشید و رها کنید
                        </p>
                        <span className="my-1.5 text-[11px] text-content-muted/60">یا</span>
                        <button
                            type="button"
                            onClick={addEmptyRow}
                            className="mt-1 inline-flex items-center gap-1.5 rounded-xl bg-primary/10 px-4 py-2 text-xs font-bold text-primary transition hover:bg-primary/20"
                        >
                            <Icon name="plus" className="size-3.5" />
                            <span>افزودن فیلد (Add Field)</span>
                        </button>
                    </>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {rows.map((row, index) => (
                <KeyValueRow
                    key={row.id}
                    row={row}
                    index={index}
                    onUpdate={(patch) => update(row.id, patch)}
                    onRemove={() => removeRow(row.id)}
                />
            ))}

            <button
                {...zoneDrop.dropProps}
                type="button"
                onClick={addEmptyRow}
                className={`flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed py-2.5 text-xs font-bold transition ${
                    zoneDrop.isOver
                        ? 'border-primary bg-primary/10 text-primary ring-2 ring-primary/20'
                        : zoneDrop.isDragging
                            ? 'border-primary/60 bg-primary/[0.04] text-primary'
                            : 'border-line-border bg-surface-card/40 text-content-muted hover:border-primary hover:bg-primary/5 hover:text-primary'
                }`}
            >
                {zoneDrop.isDragging && zoneDrop.activeField ? (
                    <>
                        <span>برای افزودن فیلد</span>
                        <span dir="ltr" className="rounded bg-primary/15 px-1.5 py-0.5 font-mono text-primary">
                            {zoneDrop.activeField.keyName}
                        </span>
                        <span>اینجا رها کنید</span>
                    </>
                ) : (
                    <>
                        <Icon name="plus" className="size-3.5" />
                        <span>افزودن فیلد جدید (Add Field)</span>
                    </>
                )}
            </button>
        </div>
    );
}
