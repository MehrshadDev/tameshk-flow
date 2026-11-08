import { useEffect, useRef, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';

export type ConditionDataType = 'string' | 'number' | 'datetime' | 'boolean' | 'array' | 'object';

export type OperatorOption = {
    value: string;
    label: string;
    unary?: boolean;
};

export type DataTypeGroup = {
    type: ConditionDataType;
    label: string;
    faLabel: string;
    badge: string;
    icon?: string;
    operators: OperatorOption[];
};

export const CONDITION_DATA_GROUPS: DataTypeGroup[] = [
    {
        type: 'string',
        label: 'String',
        faLabel: 'متن (String)',
        badge: 'T',
        operators: [
            { value: 'exists', label: 'وجود دارد (exists)', unary: true },
            { value: 'not_exists', label: 'وجود ندارد (does not exist)', unary: true },
            { value: 'empty', label: 'خالی است (is empty)', unary: true },
            { value: 'not_empty', label: 'خالی نیست (is not empty)', unary: true },
            { value: 'eq', label: 'برابر است با (is equal to)' },
            { value: 'neq', label: 'برابر نیست با (is not equal to)' },
            { value: 'contains', label: 'شامل می‌شود (contains)' },
            { value: 'not_contains', label: 'شامل نمی‌شود (does not contain)' },
            { value: 'starts_with', label: 'شروع می‌شود با (starts with)' },
            { value: 'not_starts_with', label: 'شروع نمی‌شود با (does not start with)' },
            { value: 'ends_with', label: 'پایان می‌یابد با (ends with)' },
            { value: 'not_ends_with', label: 'پایان نمی‌یابد با (does not end with)' },
            { value: 'regex', label: 'منطبق با الگوی Regex (matches regex)' },
            { value: 'not_regex', label: 'عدم تطابق با Regex (does not match regex)' },
        ],
    },
    {
        type: 'number',
        label: 'Number',
        faLabel: 'عدد (Number)',
        badge: '#',
        operators: [
            { value: 'exists', label: 'وجود دارد (exists)', unary: true },
            { value: 'not_exists', label: 'وجود ندارد (does not exist)', unary: true },
            { value: 'empty', label: 'خالی است (is empty)', unary: true },
            { value: 'not_empty', label: 'خالی نیست (is not empty)', unary: true },
            { value: 'eq', label: 'برابر است با (==)' },
            { value: 'neq', label: 'برابر نیست با (!=)' },
            { value: 'gt', label: 'بزرگ‌تر از (>)' },
            { value: 'gte', label: 'بزرگ‌تر یا مساوی (>=)' },
            { value: 'lt', label: 'کوچک‌تر از (<)' },
            { value: 'lte', label: 'کوچک‌تر یا مساوی (<=)' },
        ],
    },
    {
        type: 'datetime',
        label: 'Date & Time',
        faLabel: 'تاریخ و زمان (Date & Time)',
        badge: '📅',
        icon: 'calendar',
        operators: [
            { value: 'exists', label: 'وجود دارد (exists)', unary: true },
            { value: 'not_exists', label: 'وجود ندارد (does not exist)', unary: true },
            { value: 'empty', label: 'خالی است (is empty)', unary: true },
            { value: 'not_empty', label: 'خالی نیست (is not empty)', unary: true },
            { value: 'eq', label: 'برابر است با (is equal to)' },
            { value: 'neq', label: 'برابر نیست با (is not equal to)' },
            { value: 'date_after', label: 'بعد از تاریخ (is after)' },
            { value: 'date_before', label: 'قبل از تاریخ (is before)' },
            { value: 'date_after_or_eq', label: 'بعد یا هم‌زمان با (is after or equal to)' },
            { value: 'date_before_or_eq', label: 'قبل یا هم‌زمان با (is before or equal to)' },
        ],
    },
    {
        type: 'boolean',
        label: 'Boolean',
        faLabel: 'بولین (Boolean)',
        badge: '✓',
        operators: [
            { value: 'exists', label: 'وجود دارد (exists)', unary: true },
            { value: 'not_exists', label: 'وجود ندارد (does not exist)', unary: true },
            { value: 'empty', label: 'خالی است (is empty)', unary: true },
            { value: 'not_empty', label: 'خالی نیست (is not empty)', unary: true },
            { value: 'is_true', label: 'درست است (is true)', unary: true },
            { value: 'is_false', label: 'نادرست است (is false)', unary: true },
            { value: 'eq', label: 'برابر است با (is equal to)' },
            { value: 'neq', label: 'برابر نیست با (is not equal to)' },
        ],
    },
    {
        type: 'array',
        label: 'Array',
        faLabel: 'آرایه / لیست (Array)',
        badge: '[]',
        operators: [
            { value: 'exists', label: 'وجود دارد (exists)', unary: true },
            { value: 'not_exists', label: 'وجود ندارد (does not exist)', unary: true },
            { value: 'empty', label: 'خالی است (is empty)', unary: true },
            { value: 'not_empty', label: 'خالی نیست (is not empty)', unary: true },
            { value: 'array_contains', label: 'شامل آیتم است (contains)' },
            { value: 'array_not_contains', label: 'شامل آیتم نیست (does not contain)' },
            { value: 'array_length_eq', label: 'تعداد آیتم‌ها برابر با (length equal to)' },
            { value: 'array_length_neq', label: 'تعداد آیتم‌ها مخالف با (length not equal to)' },
            { value: 'array_length_gt', label: 'تعداد آیتم‌ها بیشتر از (length >)' },
            { value: 'array_length_gte', label: 'تعداد آیتم‌ها بیشتر یا مساوی (length >=)' },
            { value: 'array_length_lt', label: 'تعداد آیتم‌ها کمتر از (length <)' },
            { value: 'array_length_lte', label: 'تعداد آیتم‌ها کمتر یا مساوی (length <=)' },
        ],
    },
    {
        type: 'object',
        label: 'Object',
        faLabel: 'آبجکت (Object)',
        badge: '{}',
        operators: [
            { value: 'exists', label: 'وجود دارد (exists)', unary: true },
            { value: 'not_exists', label: 'وجود ندارد (does not exist)', unary: true },
            { value: 'empty', label: 'خالی است (is empty)', unary: true },
            { value: 'not_empty', label: 'خالی نیست (is not empty)', unary: true },
        ],
    },
];

const UNARY_OPERATORS = new Set([
    'exists',
    'not_exists',
    'empty',
    'not_empty',
    'is_true',
    'is_false',
]);

export const isUnaryOperator = (operator: string): boolean => UNARY_OPERATORS.has(operator);

export const inferDataTypeFromOperator = (
    operator: string,
    explicitType?: string,
): ConditionDataType => {
    if (
        explicitType &&
        CONDITION_DATA_GROUPS.some((g) => g.type === explicitType)
    ) {
        return explicitType as ConditionDataType;
    }

    if (['gt', 'gte', 'lt', 'lte'].includes(operator)) return 'number';
    if (operator.startsWith('date_')) return 'datetime';
    if (['is_true', 'is_false'].includes(operator)) return 'boolean';
    if (operator.startsWith('array_')) return 'array';

    return 'string';
};

type ConditionOperatorSelectProps = {
    dataType: ConditionDataType;
    operator: string;
    onChange: (next: { dataType: ConditionDataType; operator: string }) => void;
};

export function ConditionOperatorSelect({
                                            dataType,
                                            operator,
                                            onChange,
                                        }: ConditionOperatorSelectProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [openUpward, setOpenUpward] = useState(false);
    const [activeType, setActiveType] = useState<ConditionDataType>(dataType);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setActiveType(dataType);
    }, [dataType]);

    useEffect(() => {
        if (!isOpen) return;

        const handleOutside = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleOutside);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('mousedown', handleOutside);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen]);

    const currentGroup =
        CONDITION_DATA_GROUPS.find((g) => g.type === dataType) ?? CONDITION_DATA_GROUPS[0];
    const currentOperator =
        currentGroup.operators.find((op) => op.value === operator) ??
        CONDITION_DATA_GROUPS.flatMap((g) => g.operators).find((op) => op.value === operator) ??
        currentGroup.operators[0];

    const previewGroup =
        CONDITION_DATA_GROUPS.find((g) => g.type === activeType) ?? currentGroup;

    const toggleOpen = () => {
        if (!isOpen && containerRef.current) {
            const rect = containerRef.current.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            setOpenUpward(spaceBelow < 320 && spaceAbove > spaceBelow);
            setActiveType(dataType);
        }
        setIsOpen((prev) => !prev);
    };

    return (
        <div ref={containerRef} className="relative w-full select-none" dir="rtl">
            <button
                type="button"
                onClick={toggleOpen}
                className={`flex w-full items-center justify-between gap-2 rounded-xl border bg-field px-3 py-2 text-xs font-medium text-content-main shadow-xs transition hover:border-content-muted/40 focus:outline-none ${
                    isOpen ? 'border-primary bg-surface-card ring-2 ring-primary/20' : 'border-line-border'
                }`}
            >
                <span className="flex min-w-0 items-center gap-2">
                    <span
                        dir="ltr"
                        title={currentGroup.faLabel}
                        className="inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-lg border border-primary/25 bg-primary/10 px-1.5 font-mono text-[11px] font-bold text-primary"
                    >
                        {currentGroup.icon ? (
                            <Icon name={currentGroup.icon} className="size-3.5" />
                        ) : (
                            currentGroup.badge
                        )}
                    </span>
                    <span className="truncate font-semibold text-content-main">
                        {currentOperator?.label ?? operator}
                    </span>
                </span>

                <Icon
                    name="chevron-down"
                    className={`size-4 shrink-0 text-content-muted transition-transform duration-200 ${
                        isOpen ? (openUpward ? 'rotate-0 text-primary' : 'rotate-180 text-primary') : ''
                    }`}
                />
            </button>

            {isOpen && (
                <div
                    className={`absolute inset-x-0 z-50 flex max-h-80 overflow-hidden rounded-2xl border border-line-border bg-surface-raised shadow-2xl backdrop-blur-xl animate-in fade-in zoom-in-95 duration-100 ${
                        openUpward ? 'bottom-full mb-1.5' : 'top-full mt-1.5'
                    }`}
                >
                    <div className="w-36 shrink-0 space-y-0.5 border-l border-line-border/70 bg-surface-card/60 p-1.5">
                        <div className="px-2 py-1 text-[10px] font-bold text-content-muted">
                            نوع داده (Type)
                        </div>
                        {CONDITION_DATA_GROUPS.map((group) => {
                            const isActive = group.type === activeType;
                            return (
                                <button
                                    key={group.type}
                                    type="button"
                                    onMouseEnter={() => setActiveType(group.type)}
                                    onClick={() => setActiveType(group.type)}
                                    className={`flex w-full items-center justify-between gap-1.5 rounded-xl px-2.5 py-2 text-xs transition ${
                                        isActive
                                            ? 'bg-primary/15 font-bold text-primary'
                                            : 'font-medium text-content-main hover:bg-line-border/50'
                                    }`}
                                >
                                    <span className="flex items-center gap-2 min-w-0">
                                        <span
                                            dir="ltr"
                                            className={`inline-flex w-5 shrink-0 justify-center font-mono text-[11px] font-bold ${
                                                isActive ? 'text-primary' : 'text-content-muted'
                                            }`}
                                        >
                                            {group.icon ? (
                                                <Icon name={group.icon} className="size-3.5" />
                                            ) : (
                                                group.badge
                                            )}
                                        </span>
                                        <span className="truncate text-[11px]">{group.label}</span>
                                    </span>
                                    <Icon name="chevron-left" className="size-3.5 shrink-0 opacity-60" />
                                </button>
                            );
                        })}
                    </div>

                    <div className="custom-scrollbar flex-1 space-y-0.5 overflow-y-auto p-1.5">
                        <div className="px-2.5 py-1 text-[10px] font-bold text-content-muted">
                            عملگرهای {previewGroup.faLabel}
                        </div>
                        {previewGroup.operators.map((op) => {
                            const isSelected =
                                dataType === previewGroup.type && operator === op.value;

                            return (
                                <button
                                    key={op.value}
                                    type="button"
                                    onClick={() => {
                                        onChange({
                                            dataType: previewGroup.type,
                                            operator: op.value,
                                        });
                                        setIsOpen(false);
                                    }}
                                    className={`flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-start text-xs transition ${
                                        isSelected
                                            ? 'bg-primary/10 font-bold text-primary'
                                            : 'font-medium text-content-main hover:bg-line-border/60 hover:text-primary'
                                    }`}
                                >
                                    <span className="truncate">{op.label}</span>
                                    {isSelected && (
                                        <Icon name="check" className="size-3.5 shrink-0 text-primary" />
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
