import { useState } from 'react';
import {
    inferDataTypeFromOperator,
    moveArrayItem,
    RuleCardShell,
    type ConditionDataType,
} from '@canvas/components/settings/ConditionOperatorSelect';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { Icon } from '@canvas/components/ui/Icon';

export type SwitchRuleItem = {
    id: string;
    data_type: ConditionDataType;
    field: string;
    operator: string;
    value: string;
    rename_output: boolean;
    output_name: string;
};

type SwitchRulesEditorProps = {
    value: unknown;
    onChange: (value: SwitchRuleItem[]) => void;
    incomingData?: unknown;
    fullConfig?: Record<string, unknown>;
};

export const normalizeSwitchRules = (
    val: unknown,
    fullConfig?: Record<string, unknown>,
): SwitchRuleItem[] => {
    if (Array.isArray(val) && val.length > 0) {
        return val.map((item, idx) => {
            const op = String(item?.operator ?? 'eq');
            return {
                id: typeof item?.id === 'string' && item.id.trim() !== '' ? item.id : `case_${idx + 1}`,
                data_type: inferDataTypeFromOperator(op, item?.data_type),
                field: String(item?.field ?? ''),
                operator: op,
                value: String(item?.value ?? ''),
                rename_output: Boolean(item?.rename_output),
                output_name: String(item?.output_name ?? ''),
            };
        });
    }

    if (fullConfig && ('case_1' in fullConfig || 'case_2' in fullConfig || 'case_3' in fullConfig)) {
        const baseField = String(fullConfig.value_to_match ?? '');
        const baseOp = String(fullConfig.operator ?? 'eq');
        const legacy: SwitchRuleItem[] = [];

        (['case_1', 'case_2', 'case_3'] as const).forEach((key) => {
            const v = String(fullConfig[key] ?? '').trim();
            if (v !== '' || key === 'case_1') {
                legacy.push({
                    id: key,
                    data_type: inferDataTypeFromOperator(baseOp),
                    field: baseField,
                    operator: baseOp,
                    value: v,
                    rename_output: false,
                    output_name: '',
                });
            }
        });

        if (legacy.length > 0) {
            return legacy;
        }
    }

    return [
        {
            id: 'case_1',
            data_type: 'string',
            field: '',
            operator: 'eq',
            value: '',
            rename_output: false,
            output_name: '',
        },
    ];
};

const nextRuleId = (rules: SwitchRuleItem[]): string => {
    const used = new Set(rules.map((r) => r.id));
    let n = 1;
    while (used.has(`case_${n}`)) {
        n++;
    }
    return `case_${n}`;
};

export function SwitchRulesEditor({ value, onChange, incomingData, fullConfig }: SwitchRulesEditorProps) {
    const rules = normalizeSwitchRules(value, fullConfig);
    const [draggedId, setDraggedId] = useState<string | null>(null);
    const [dragOverId, setDragOverId] = useState<string | null>(null);

    const update = (id: string, patch: Partial<SwitchRuleItem>) => {
        onChange(rules.map((r) => (r.id === id ? { ...r, ...patch } : r)));
    };

    const addRule = () => {
        if (rules.length >= 20) return;

        const firstRule = rules[0];
        onChange([
            ...rules,
            {
                id: nextRuleId(rules),
                data_type: firstRule?.data_type ?? 'string',
                field: firstRule?.field ?? '',
                operator: 'eq',
                value: '',
                rename_output: false,
                output_name: '',
            },
        ]);
    };

    const removeRule = (id: string) => {
        if (rules.length <= 1) {
            onChange([
                {
                    id: 'case_1',
                    data_type: 'string',
                    field: '',
                    operator: 'eq',
                    value: '',
                    rename_output: false,
                    output_name: '',
                },
            ]);
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
            {rules.map((rule, idx) => {
                const defaultLabel = `حالت ${idx + 1}`;
                const badgeTitle =
                    rule.rename_output && rule.output_name.trim() !== ''
                        ? rule.output_name.trim()
                        : defaultLabel;

                return (
                    <RuleCardShell
                        key={rule.id}
                        idPrefix="switch"
                        rule={rule}
                        index={idx}
                        total={rules.length}
                        accentColor="orange"
                        badgeText={badgeTitle}
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
                        footer={
                            <div className="flex flex-wrap items-center justify-between gap-2 border-t border-line-border/50 pt-2">
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked={rule.rename_output}
                                        onClick={() =>
                                            update(rule.id, {
                                                rename_output: !rule.rename_output,
                                                output_name:
                                                    !rule.rename_output && rule.output_name.trim() === ''
                                                        ? defaultLabel
                                                        : rule.output_name,
                                            })
                                        }
                                        dir="ltr"
                                        className={`relative inline-flex h-4.5 w-8 shrink-0 items-center rounded-full transition-colors duration-200 ${
                                            rule.rename_output ? 'bg-emerald-500' : 'bg-line-border'
                                        }`}
                                    >
                                        <span
                                            className={`inline-block size-3 transform rounded-full bg-white shadow-xs transition-transform duration-200 ${
                                                rule.rename_output ? 'translate-x-4' : 'translate-x-0.5'
                                            }`}
                                        />
                                    </button>
                                    <span className="text-[11px] font-medium text-content-muted">
                                        تغییر نام خروجی (Rename Output)
                                    </span>
                                </div>

                                {rule.rename_output && (
                                    <input
                                        type="text"
                                        dir="auto"
                                        maxLength={30}
                                        value={rule.output_name}
                                        placeholder={defaultLabel}
                                        onChange={(e) =>
                                            update(rule.id, { output_name: e.target.value })
                                        }
                                        className={`${inputClass} h-8! max-w-44 py-1! text-xs`}
                                    />
                                )}
                            </div>
                        }
                    />
                );
            })}

            {rules.length < 20 && (
                <button
                    type="button"
                    onClick={addRule}
                    className="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-line-border bg-surface-card/50 py-2.5 text-xs font-bold text-content-muted transition hover:border-primary hover:bg-primary/5 hover:text-primary active:scale-[0.99]"
                >
                    <Icon name="plus" className="size-3.5" />
                    <span>افزودن قانون مسیریابی (Add Routing Rule)</span>
                </button>
            )}
        </div>
    );
}
