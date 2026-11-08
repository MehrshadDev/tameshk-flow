import { ConfigFieldInput } from '@canvas/components/settings/ConfigFieldInput';
import {
    ConditionOperatorSelect,
    inferDataTypeFromOperator,
    isUnaryOperator,
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

const getSecondFieldPlaceholder = (dataType: ConditionDataType, operator: string): string => {
    if (operator.startsWith('array_length_')) return 'مثلاً: 3';
    if (dataType === 'number') return 'مثلاً: 100';
    if (dataType === 'datetime') return 'مثلاً: 2026-11-08 14:30:00';
    if (dataType === 'boolean') return 'true یا false';
    return 'مثلاً: /start یا paid';
};

export function SwitchRulesEditor({ value, onChange, incomingData, fullConfig }: SwitchRulesEditorProps) {
    const rules = normalizeSwitchRules(value, fullConfig);

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

    return (
        <div className="space-y-3.5">
            {rules.map((rule, idx) => {
                const defaultLabel = `حالت ${idx + 1}`;
                const badgeTitle =
                    rule.rename_output && rule.output_name.trim() !== ''
                        ? rule.output_name.trim()
                        : defaultLabel;
                const hideSecondValue = isUnaryOperator(rule.operator);

                return (
                    <div
                        key={rule.id}
                        className="group relative flex flex-col gap-3 rounded-2xl border border-line-border/90 bg-surface-card p-4 shadow-sm transition-all duration-200 hover:border-line-border hover:shadow-md"
                    >
                        <div className="flex items-center justify-between border-b border-line-border/50 pb-2.5">
                            <div className="flex items-center gap-2">
                                <span className="flex size-5 items-center justify-center rounded-full bg-orange-500/15 text-[11px] font-bold text-orange-500">
                                    {idx + 1}
                                </span>
                                <span className="text-xs font-bold text-content-main">
                                    قانون مسیریابی {idx + 1}
                                </span>
                                <span className="rounded-md border border-orange-500/30 bg-orange-500/10 px-2 py-0.5 text-[10px] font-bold text-orange-500">
                                    {badgeTitle}
                                </span>
                            </div>

                            <button
                                type="button"
                                onClick={() => removeRule(rule.id)}
                                aria-label="حذف قانون"
                                title="حذف این شاخه"
                                className="flex size-7 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                            >
                                <Icon name="trash" className="size-3.5" />
                            </button>
                        </div>

                        <div className="space-y-3">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold text-content-muted">
                                    مقدار اول (Value 1)
                                </label>
                                <ConfigFieldInput
                                    id={`switch-field-${rule.id}`}
                                    field={{
                                        type: 'text',
                                        placeholder: 'مثلاً: {{ input.text }} یا {{ input.status }}',
                                    }}
                                    value={rule.field}
                                    incomingData={incomingData}
                                    onChange={(val) => update(rule.id, { field: String(val ?? '') })}
                                />
                            </div>

                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold text-content-muted">
                                    نوع داده و عملگر مقایسه
                                </label>
                                <ConditionOperatorSelect
                                    dataType={rule.data_type}
                                    operator={rule.operator}
                                    onChange={({ dataType, operator }) =>
                                        update(rule.id, {
                                            data_type: dataType,
                                            operator,
                                            value: isUnaryOperator(operator) ? '' : rule.value,
                                        })
                                    }
                                />
                            </div>

                            {!hideSecondValue && (
                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold text-content-muted">
                                        مقدار دوم (Value 2)
                                    </label>
                                    <ConfigFieldInput
                                        id={`switch-val-${rule.id}`}
                                        field={{
                                            type: 'text',
                                            placeholder: getSecondFieldPlaceholder(
                                                rule.data_type,
                                                rule.operator,
                                            ),
                                        }}
                                        value={rule.value}
                                        incomingData={incomingData}
                                        onChange={(val) => update(rule.id, { value: String(val ?? '') })}
                                    />
                                </div>
                            )}

                            <div className="space-y-2.5 border-t border-line-border/50 pt-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-[11px] font-semibold text-content-main">
                                        تغییر نام خروجی (Rename Output)
                                    </span>
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
                                        className={`relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200 ${
                                            rule.rename_output ? 'bg-emerald-500' : 'bg-line-border'
                                        }`}
                                    >
                                        <span
                                            className={`inline-block size-3.5 transform rounded-full bg-white shadow-xs transition-transform duration-200 ${
                                                rule.rename_output ? 'translate-x-4.5' : 'translate-x-0.5'
                                            }`}
                                        />
                                    </button>
                                </div>

                                {rule.rename_output && (
                                    <div className="space-y-1">
                                        <label className="text-[11px] font-semibold text-content-muted">
                                            نام نمایشی خروجی روی نود
                                        </label>
                                        <input
                                            type="text"
                                            dir="auto"
                                            maxLength={30}
                                            value={rule.output_name}
                                            placeholder={defaultLabel}
                                            onChange={(e) =>
                                                update(rule.id, { output_name: e.target.value })
                                            }
                                            className={`${inputClass} text-xs`}
                                        />
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                );
            })}

            {rules.length < 20 && (
                <button
                    type="button"
                    onClick={addRule}
                    className="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-line-border bg-surface-card/50 py-3 text-xs font-bold text-content-muted transition hover:border-primary hover:bg-primary/5 hover:text-primary active:scale-[0.99]"
                >
                    <Icon name="plus" className="size-4" />
                    <span>افزودن قانون مسیریابی (Add Routing Rule)</span>
                </button>
            )}
        </div>
    );
}
