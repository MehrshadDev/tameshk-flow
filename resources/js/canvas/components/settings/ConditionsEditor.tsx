import { ConfigFieldInput } from '@canvas/components/settings/ConfigFieldInput';
import {
    ConditionOperatorSelect,
    inferDataTypeFromOperator,
    isUnaryOperator,
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

const getSecondFieldPlaceholder = (dataType: ConditionDataType, operator: string): string => {
    if (operator.startsWith('array_length_')) return 'مثلاً: 3';
    if (dataType === 'number') return 'مثلاً: 100';
    if (dataType === 'datetime') return 'مثلاً: 2026-11-08 14:30:00';
    if (dataType === 'boolean') return 'true یا false';
    return 'مقدار مورد نظر';
};

export function ConditionsEditor({ value, onChange, incomingData }: ConditionsEditorProps) {
    const rules = toRules(value);

    const update = (id: string, patch: Partial<ConditionRule>) => {
        const next = rules.map((r) => (r.id === id ? { ...r, ...patch } : r));
        onChange(next);
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

    return (
        <div className="space-y-3">
            {rules.map((rule, idx) => {
                const hideSecondValue = isUnaryOperator(rule.operator);

                return (
                    <div
                        key={rule.id}
                        className="group relative flex flex-col gap-3 rounded-2xl border border-line-border/90 bg-surface-card p-4 shadow-sm transition-all duration-200 hover:border-line-border hover:shadow-md"
                    >
                        <div className="flex items-center justify-between border-b border-line-border/50 pb-2.5">
                            <div className="flex items-center gap-2">
                                <span className="flex size-5 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-primary">
                                    {idx + 1}
                                </span>
                                <span className="text-xs font-bold text-content-main">
                                    شرط شماره {idx + 1}
                                </span>
                            </div>

                            <button
                                type="button"
                                onClick={() => removeRule(rule.id)}
                                aria-label="حذف شرط"
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
                                    id={`rule-field-${rule.id}`}
                                    field={{ type: 'text', placeholder: 'مثلاً: {{ input.status }}' }}
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
                                        id={`rule-val-${rule.id}`}
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
                        </div>
                    </div>
                );
            })}

            <button
                type="button"
                onClick={addRule}
                className="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-line-border bg-surface-card/50 py-3 text-xs font-bold text-content-muted transition hover:border-primary hover:bg-primary/5 hover:text-primary active:scale-[0.99]"
            >
                <Icon name="plus" className="size-4" />
                <span>افزودن شرط جدید</span>
            </button>
        </div>
    );
}
