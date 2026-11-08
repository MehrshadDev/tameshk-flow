import { useState } from 'react';
import {
    inferDataTypeFromOperator,
    moveArrayItem,
    RuleCardShell,
    type ConditionDataType,
} from '@canvas/components/settings/ConditionOperatorSelect';
import { Icon } from '@canvas/components/ui/Icon';

export type ConditionRule = {
    id: string;
    data_type: ConditionDataType;
    field: string;
    operator: string;
    value: string;
};

type ConditionsEditorProps = {
    value: unknown;
    onChange: (value: ConditionRule[]) => void;
    incomingData?: unknown;
};

const toRules = (val: unknown): ConditionRule[] => {
    if (!Array.isArray(val) || val.length === 0) {
        return [{ id: crypto.randomUUID(), data_type: 'string', field: '', operator: 'eq', value: '' }];
    }

    return val.map((item) => {
        const op = String(item?.operator ?? 'eq');
        return {
            id: item?.id ?? crypto.randomUUID(),
            data_type: inferDataTypeFromOperator(op, item?.data_type),
            field: String(item?.field ?? ''),
            operator: op,
            value: String(item?.value ?? ''),
        };
    });
};

export function ConditionsEditor({ value, onChange, incomingData }: ConditionsEditorProps) {
    const rules = toRules(value);
    const [draggedId, setDraggedId] = useState<string | null>(null);
    const [dragOverId, setDragOverId] = useState<string | null>(null);

    const update = (id: string, patch: Partial<ConditionRule>) => {
        onChange(rules.map((r) => (r.id === id ? { ...r, ...patch } : r)));
    };

    const addRule = () => {
        onChange([
            ...rules,
            { id: crypto.randomUUID(), data_type: 'string', field: '', operator: 'eq', value: '' },
        ]);
    };

    const removeRule = (id: string) => {
        if (rules.length <= 1) {
            onChange([{ id: crypto.randomUUID(), data_type: 'string', field: '', operator: 'eq', value: '' }]);
            return;
        }
        onChange(rules.filter((r) => r.id !== id));
    };

    const moveRule = (fromIndex: number, toIndex: number) => {
        const reordered = moveArrayItem(rules, fromIndex, toIndex);
        if (reordered) onChange(reordered);
    };

    return (
        <div className="space-y-2.5">
            {rules.map((rule, idx) => (
                <RuleCardShell
                    key={rule.id}
                    idPrefix="cond"
                    rule={rule}
                    index={idx}
                    total={rules.length}
                    accentColor="primary"
                    incomingData={incomingData}
                    isDragging={draggedId === rule.id}
                    isDragOver={dragOverId === rule.id && draggedId !== rule.id}
                    onDragStart={() => setDraggedId(rule.id)}
                    onDragOver={() => {
                        if (draggedId) setDragOverId(rule.id);
                    }}
                    onDrop={() => {
                        if (draggedId && draggedId !== rule.id) {
                            moveRule(
                                rules.findIndex((r) => r.id === draggedId),
                                idx,
                            );
                        }
                        setDraggedId(null);
                        setDragOverId(null);
                    }}
                    onDragEnd={() => {
                        setDraggedId(null);
                        setDragOverId(null);
                    }}
                    onMoveUp={() => moveRule(idx, idx - 1)}
                    onMoveDown={() => moveRule(idx, idx + 1)}
                    onRemove={() => removeRule(rule.id)}
                    onUpdate={(patch) => update(rule.id, patch)}
                />
            ))}

            <button
                type="button"
                onClick={addRule}
                className="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-line-border bg-surface-card/50 py-2.5 text-xs font-bold text-content-muted transition hover:border-primary hover:bg-primary/5 hover:text-primary active:scale-[0.99]"
            >
                <Icon name="plus" className="size-3.5" />
                <span>افزودن شرط جدید</span>
            </button>
        </div>
    );
}
