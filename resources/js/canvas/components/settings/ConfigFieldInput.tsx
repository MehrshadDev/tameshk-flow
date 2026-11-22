import { useRef, useState } from 'react';
import { ConditionsEditor } from '@canvas/components/settings/ConditionsEditor';
import { CredentialSelect } from '@canvas/components/settings/CredentialSelect';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { KeyValueEditor } from '@canvas/components/settings/KeyValueEditor';
import { SetFieldsEditor } from '@canvas/components/settings/SetFieldsEditor';
import { SwitchRulesEditor } from '@canvas/components/settings/SwitchRulesEditor';
import { ModelSelect } from '@canvas/components/settings/ModelSelect';
import { PasswordField } from '@canvas/components/ui/PasswordField';
import { Select } from '@canvas/components/ui/Select';
import { asString, hasRtlChars, normalizeOptions, textDir } from '@canvas/lib/fields';
import { useFieldDrop } from '@canvas/lib/fieldDrag';
import type { ConfigField } from '@canvas/types/graph';
import { MultiSelectField } from '@canvas/components/settings/MultiSelectField';
import { DynamicSelect } from '@canvas/components/settings/DynamicSelect';

type ConfigFieldInputProps = {
    id: string;
    field: ConfigField;
    value: unknown;
    onChange: (value: unknown) => void;
    incomingData?: unknown;
    fullConfig?: Record<string, unknown>;
};

const MATH_FUNCTIONS = new Set(['sqrt', 'abs', 'round', 'floor', 'ceil', 'sin', 'cos', 'min', 'max']);

const PERSIAN_DIGIT_MAP: Record<string, string> = {
    '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
    '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
    '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
};

const normalizeDigits = (val: string): string =>
    val.replace(/[۰-۹٠-٩]/g, (ch) => PERSIAN_DIGIT_MAP[ch] ?? ch);

const toNumeric = (val: unknown): number | null => {
    if (typeof val === 'number' && Number.isFinite(val)) return val;
    if (typeof val !== 'string') return null;
    const clean = normalizeDigits(val).replace(/[,٬\s]/g, '');
    if (clean === '') return null;
    const num = Number(clean);
    return Number.isFinite(num) ? num : null;
};

const getByPath = (source: unknown, path: string): { found: boolean; value: unknown } => {
    if (!path) {
        return { found: source !== undefined, value: source };
    }

    const parts = path.replace(/\[(\d+)]/g, '.$1').split('.').filter(Boolean);
    let current: unknown = source;

    for (const part of parts) {
        if (current !== null && current !== undefined && typeof current === 'object' && part in (current as Record<string, unknown>)) {
            current = (current as Record<string, unknown>)[part];
        } else {
            return { found: false, value: undefined };
        }
    }

    return { found: true, value: current };
};

const resolveSingleToken = (
    expr: string,
    inputObj: unknown,
    nodesMap: Record<string, unknown>,
): { found: boolean; value: unknown } => {
    const cleanExpr = expr.trim();

    const nodeSelectorMatch = cleanExpr.match(/^\$\(['"](.+?)['"]\)(?:\.item)?(?:\.json)?(?:\.(.+))?$/);
    if (nodeSelectorMatch) {
        const nodeName = nodeSelectorMatch[1];
        const propPath = nodeSelectorMatch[2] ?? '';
        const targetNodeData = nodesMap[nodeName] ?? nodesMap[nodeName.replace(/\s+/g, '_')];

        if (targetNodeData === undefined) {
            return { found: false, value: undefined };
        }

        return getByPath(targetNodeData, propPath);
    }

    if (cleanExpr.startsWith('nodes.')) {
        const rest = cleanExpr.slice('nodes.'.length);
        const dotIndex = rest.indexOf('.');
        const nodeKey = dotIndex === -1 ? rest : rest.slice(0, dotIndex);
        const propPath = dotIndex === -1 ? '' : rest.slice(dotIndex + 1);
        const targetNodeData = nodesMap[nodeKey];

        return getByPath(targetNodeData, propPath);
    }

    const cleanInputPath =
        cleanExpr === 'input'
            ? ''
            : cleanExpr.startsWith('input.')
                ? cleanExpr.slice('input.'.length)
                : cleanExpr;

    const directRes = getByPath(inputObj, cleanInputPath);
    if (directRes.found) {
        return directRes;
    }

    for (const nodeOutput of Object.values(nodesMap)) {
        const upRes = getByPath(nodeOutput, cleanInputPath);
        if (upRes.found) {
            return upRes;
        }
    }

    return { found: false, value: undefined };
};

const validateMathExpression = (
    expr: string,
    inputObj: unknown,
    nodesMap: Record<string, unknown>,
): boolean => {
    if (!/[+\-*/%^()]|\b(?:sqrt|abs|round|floor|ceil|sin|cos|min|max)\s*\(/i.test(expr)) {
        return false;
    }

    let unresolved = false;

    let replaced = expr.replace(
        /\$\(['"][^'"]+['"]\)(?:\.item)?(?:\.json)?(?:\.[a-zA-Z0-9_.[\]]+)?/g,
        (match) => {
            const res = resolveSingleToken(match, inputObj, nodesMap);
            const num = res.found ? toNumeric(res.value) : null;
            if (num === null) {
                unresolved = true;
                return '';
            }
            return String(num);
        },
    );

    if (unresolved) return false;

    replaced = replaced.replace(
        /\b([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+|\[\d+])*)/g,
        (match) => {
            if (MATH_FUNCTIONS.has(match.toLowerCase())) {
                return match.toLowerCase();
            }
            const res = resolveSingleToken(match, inputObj, nodesMap);
            const num = res.found ? toNumeric(res.value) : null;
            if (num === null) {
                unresolved = true;
                return '';
            }
            return String(num);
        },
    );

    if (unresolved) return false;

    const normalized = normalizeDigits(replaced).replace(/\*\*/g, '^').replace(/\s+/g, '');
    return normalized !== '' && /^[0-9+\-*/^%().,a-z]+$/i.test(normalized);
};

const resolveExpression = (contextData: unknown, text: string): { found: boolean; value: unknown } => {
    const matches = Array.from(text.matchAll(/\{\{\s*(.+?)\s*}}/g));

    if (matches.length === 0) {
        return { found: true, value: undefined };
    }

    const isStructuredContext =
        contextData !== null
        && typeof contextData === 'object'
        && ('input' in (contextData as Record<string, unknown>) || 'nodes' in (contextData as Record<string, unknown>));

    const inputObj = isStructuredContext ? (contextData as Record<string, unknown>).input : contextData;
    const nodesMap = isStructuredContext
        ? ((contextData as Record<string, unknown>).nodes as Record<string, unknown> | undefined) ?? {}
        : {};

    let allFound = true;
    let lastValue: unknown = undefined;

    for (const match of matches) {
        const expr = match[1].trim();
        const direct = resolveSingleToken(expr, inputObj, nodesMap);

        if (direct.found) {
            lastValue = direct.value;
            continue;
        }

        if (validateMathExpression(expr, inputObj, nodesMap)) {
            continue;
        }

        allFound = false;
    }

    return { found: allFound, value: lastValue };
};

function NumberField({ id, field, value, onChange }: ConfigFieldInputProps) {
    const [draft, setDraft] = useState(asString(value));
    const allowDecimal = !field.integer;
    const pattern = allowDecimal ? /^-?\d*\.?\d*$/ : /^-?\d*$/;

    const edit = (raw: string) => {
        if (!pattern.test(raw)) return;
        setDraft(raw);

        if (raw === '' || raw === '-' || raw === '.' || raw === '-.') {
            onChange(null);
            return;
        }

        const parsed = Number(raw);
        if (!Number.isFinite(parsed)) {
            onChange(null);
            return;
        }

        if (!allowDecimal && !Number.isInteger(parsed)) {
            onChange(null);
            return;
        }

        onChange(parsed);
    };

    const clamp = () => {
        const parsed = Number(draft);
        if (draft === '' || !Number.isFinite(parsed)) {
            setDraft('');
            onChange(null);
            return;
        }
        const next = Math.min(field.max ?? Infinity, Math.max(field.min ?? -Infinity, parsed));
        setDraft(String(next));
        onChange(next);
    };

    return (
        <input
            id={id}
            type="text"
            inputMode={allowDecimal ? 'decimal' : 'numeric'}
            autoComplete="off"
            dir="ltr"
            value={draft}
            placeholder={field.placeholder}
            onChange={(e) => edit(e.target.value)}
            onBlur={clamp}
            className={inputClass}
        />
    );
}

export function ConfigFieldInput(props: ConfigFieldInputProps) {
    const { id, field, value, onChange, incomingData, fullConfig } = props;
    const text = asString(value);
    const hasExpressionSyntax = /\{\{.+?}}/.test(text);
    const [isExpressionMode, setIsExpressionMode] = useState<boolean>(() => hasExpressionSyntax);
    const isExpression = isExpressionMode || hasExpressionSyntax;
    const hasText = text.trim() !== '';
    const controlRef = useRef<HTMLInputElement | HTMLTextAreaElement | null>(null);

    const insertExpression = (expr: string) => {
        setIsExpressionMode(true);
        const el = controlRef.current;

        if (el && document.activeElement === el) {
            const start = el.selectionStart ?? el.value.length;
            const end = el.selectionEnd ?? el.value.length;
            const current = el.value;
            onChange(current.substring(0, start) + expr + current.substring(end));
            return;
        }

        onChange(text === '' ? expr : text + expr);
    };

    const { isDragging, isOver, dropProps } = useFieldDrop((dragged) => {
        insertExpression(dragged.expression);
    });

    const hasIncomingRun = (() => {
        if (!incomingData || typeof incomingData !== 'object') return false;
        if ('input' in incomingData || 'nodes' in incomingData) {
            const structured = incomingData as { input?: unknown; nodes?: Record<string, unknown> };
            return structured.input !== undefined || Object.keys(structured.nodes ?? {}).length > 0;
        }
        return true;
    })();

    const evaluation = isExpression && hasText && hasIncomingRun ? resolveExpression(incomingData, text) : null;
    const isValid = evaluation ? evaluation.found : true;

    const resolvedDir: 'rtl' | 'ltr' =
        field.type === 'code' || isExpression
            ? 'ltr'
            : textDir(text, field.placeholder);

    const isRtl = resolvedDir === 'rtl';
    const useMono = field.type === 'code' || (isExpression && hasText && !hasRtlChars(text));

    const expressionStyles = isExpression && hasText
        ? (hasIncomingRun
            ? (isValid
                ? `border-emerald-500/50! bg-emerald-500/[0.04]! text-emerald-700 dark:text-emerald-400 focus:border-emerald-500! focus:ring-emerald-500/15! ${useMono ? 'font-mono' : ''}`
                : `border-primary/50! bg-primary/[0.04]! text-primary focus:border-primary! focus:ring-primary/15! ${useMono ? 'font-mono' : ''}`)
            : (useMono ? 'font-mono' : ''))
        : '';

    const dropHighlight = isOver
        ? 'border-dashed! border-primary! bg-primary/[0.06]! ring-2! ring-primary/25!'
        : isDragging
            ? 'border-dashed! border-primary/55!'
            : '';

    const fxColor = isExpression && hasText && hasIncomingRun
        ? (isValid ? 'text-emerald-500' : 'text-primary')
        : 'text-content-muted/70';

    if (field.type === 'switch_rules') {
        return (
            <SwitchRulesEditor
                value={value}
                onChange={onChange}
                incomingData={incomingData}
                fullConfig={fullConfig}
            />
        );
    }

    if (field.type === 'conditions') {
        return <ConditionsEditor value={value} onChange={onChange} incomingData={incomingData} />;
    }

    if (field.type === 'set_fields') {
        return <SetFieldsEditor value={value} onChange={onChange} />;
    }

    if (field.type === 'keyvalue') {
        return <KeyValueEditor value={value} onChange={onChange} />;
    }

    if (field.type === 'credential') {
        return (
            <CredentialSelect
                id={id}
                credentialType={field.credential_type ?? ''}
                value={value}
                onChange={onChange}
            />
        );
    }

    if (field.type === 'dynamic_select') {
        return (
            <DynamicSelect
                id={id}
                field={field}
                value={value}
                onChange={onChange}
                fullConfig={fullConfig}
            />
        );
    }

    if (field.type === 'model_select') {
        const credentialId = fullConfig?.credential_id ? Number(fullConfig.credential_id) : null;
        return <ModelSelect credentialId={credentialId} value={value} onChange={(val) => onChange(val)} />;
    }

    if (field.type === 'multiselect') {
        return <MultiSelectField field={field} value={value} onChange={onChange} />;
    }

    if (field.type === 'password') {
        return (
            <PasswordField
                id={id}
                value={text}
                placeholder={field.placeholder}
                onChange={(val) => onChange(val)}
            />
        );
    }

    if (field.type === 'number') {
        return <NumberField {...props} />;
    }

    if (field.type === 'boolean') {
        const checked = value === true || value === 'true' || value === '1' || value === 1;

        return (
            <button
                id={id}
                type="button"
                role="switch"
                aria-checked={checked}
                onClick={() => onChange(!checked)}
                className="group flex w-full items-center justify-between rounded-xl border border-field-border bg-field px-3.5 py-2.5 text-start transition-colors"
            >
                <span className="text-xs font-medium text-content-main">
                    {field.label}
                </span>

                <span
                    dir="ltr"
                    className={`relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200 ${
                        checked ? 'bg-emerald-500' : 'bg-line-border'
                    }`}
                >
                    <span
                        className={`inline-block size-3.5 transform rounded-full bg-white shadow-xs transition-transform duration-200 ${
                            checked ? 'translate-x-4.5' : 'translate-x-0.5'
                        }`}
                    />
                </span>
            </button>
        );
    }

    if (field.type === 'select') {
        return (
            <Select
                id={id}
                value={text}
                placeholder="انتخاب کنید"
                options={normalizeOptions(field.options)}
                onChange={(event) => onChange(event.target.value)}
            />
        );
    }

    const supportsExpressionToggle = field.type === 'text' || field.type === 'textarea' || field.type === 'code';
    const isMultiline = field.type === 'textarea' || field.type === 'code';
    const textareaRows = Math.min(10, Math.max(4, text.split('\n').length + 1));

    const fxPadding = isExpression
        ? 'pl-9 text-left'
        : (isRtl ? 'text-right' : 'text-left');

    return (
        <div className="space-y-1.5">
            {supportsExpressionToggle && (
                <div className="flex items-center justify-between">
                    <span className="text-[10px] text-content-muted">
                        {isExpression && hasText && hasIncomingRun && (
                            isValid
                                ? <span className="font-bold text-emerald-500">معتبر</span>
                                : <span className="font-bold text-primary">نامعتبر</span>
                        )}
                    </span>

                    <div className="flex items-center rounded-lg border border-line-border bg-surface-base p-0.5 text-[10px] font-semibold">
                        <button
                            type="button"
                            onClick={() => setIsExpressionMode(false)}
                            className={`rounded-md px-2 py-0.5 transition ${
                                !isExpression
                                    ? 'bg-surface-raised text-content-main shadow-2xs'
                                    : 'text-content-muted hover:text-content-main'
                            }`}
                        >
                            ثابت
                        </button>
                        <button
                            type="button"
                            onClick={() => setIsExpressionMode(true)}
                            className={`rounded-md px-2 py-0.5 transition ${
                                isExpression
                                    ? 'bg-primary text-white shadow-2xs'
                                    : 'text-content-muted hover:text-content-main'
                            }`}
                        >
                            متغیر (fx)
                        </button>
                    </div>
                </div>
            )}

            <div className="relative" {...dropProps}>
                {isExpression && (
                    <span
                        className={`pointer-events-none absolute inset-y-0 left-0 z-10 flex pl-3 text-xs font-black italic leading-5 ${fxColor} ${
                            isMultiline ? 'items-start pt-2.5' : 'items-center'
                        }`}
                        dir="ltr"
                    >
                        fx
                    </span>
                )}

                {field.type === 'textarea' ? (
                    <textarea
                        ref={(el) => {
                            controlRef.current = el;
                        }}
                        id={id}
                        rows={textareaRows}
                        autoComplete="off"
                        spellCheck={false}
                        value={text}
                        maxLength={10000}
                        placeholder={isExpression ? '{{ input.property }}' : field.placeholder}
                        dir={resolvedDir}
                        onChange={(event) => onChange(event.target.value)}
                        className={`${inputClass} custom-scrollbar resize-none leading-6 ${fxPadding} ${expressionStyles} ${dropHighlight}`}
                    />
                ) : field.type === 'code' ? (
                    <textarea
                        ref={(el) => {
                            controlRef.current = el;
                        }}
                        id={id}
                        rows={8}
                        dir="ltr"
                        autoComplete="off"
                        spellCheck={false}
                        value={text}
                        maxLength={10000}
                        placeholder={field.placeholder}
                        onChange={(event) => onChange(event.target.value)}
                        className={`${inputClass} custom-scrollbar resize-none font-mono text-xs leading-5 ${
                            isExpression ? 'pl-9 text-left' : 'text-left'
                        } ${expressionStyles} ${dropHighlight}`}
                    />
                ) : (
                    <input
                        ref={(el) => {
                            controlRef.current = el;
                        }}
                        id={id}
                        type="text"
                        autoComplete="off"
                        spellCheck={false}
                        value={text}
                        maxLength={2000}
                        placeholder={isExpression ? '{{ input.property }}' : field.placeholder}
                        dir={resolvedDir}
                        onChange={(event) => onChange(event.target.value)}
                        className={`${inputClass} ${fxPadding} ${expressionStyles} ${dropHighlight}`}
                    />
                )}
            </div>
        </div>
    );
}
