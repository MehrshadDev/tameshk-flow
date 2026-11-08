import { useEffect, useLayoutEffect, useRef, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { ConfigFieldInput } from '@canvas/components/settings/ConfigFieldInput';
import { Icon } from '@canvas/components/ui/Icon';

export type ConditionDataType = 'string' | 'number' | 'datetime' | 'boolean' | 'array' | 'object';

export type OperatorOption = {
    value: string;
    label: string;
    shortEn: string;
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
        faLabel: 'متن',
        badge: 'T',
        operators: [
            { value: 'exists', label: 'وجود دارد', shortEn: 'exists', unary: true },
            { value: 'not_exists', label: 'وجود ندارد', shortEn: 'not exists', unary: true },
            { value: 'empty', label: 'خالی است', shortEn: 'is empty', unary: true },
            { value: 'not_empty', label: 'خالی نیست', shortEn: 'not empty', unary: true },
            { value: 'eq', label: 'برابر است با', shortEn: 'equal' },
            { value: 'neq', label: 'برابر نیست با', shortEn: 'not equal' },
            { value: 'contains', label: 'شامل می‌شود', shortEn: 'contains' },
            { value: 'not_contains', label: 'شامل نمی‌شود', shortEn: 'not contains' },
            { value: 'starts_with', label: 'شروع می‌شود با', shortEn: 'starts with' },
            { value: 'not_starts_with', label: 'شروع نمی‌شود با', shortEn: 'not starts' },
            { value: 'ends_with', label: 'پایان می‌یابد با', shortEn: 'ends with' },
            { value: 'not_ends_with', label: 'پایان نمی‌یابد با', shortEn: 'not ends' },
            { value: 'regex', label: 'منطبق با الگوی Regex', shortEn: 'regex' },
            { value: 'not_regex', label: 'عدم تطابق با Regex', shortEn: 'not regex' },
        ],
    },
    {
        type: 'number',
        label: 'Number',
        faLabel: 'عدد',
        badge: '#',
        operators: [
            { value: 'exists', label: 'وجود دارد', shortEn: 'exists', unary: true },
            { value: 'not_exists', label: 'وجود ندارد', shortEn: 'not exists', unary: true },
            { value: 'empty', label: 'خالی است', shortEn: 'is empty', unary: true },
            { value: 'not_empty', label: 'خالی نیست', shortEn: 'not empty', unary: true },
            { value: 'eq', label: 'برابر است با', shortEn: '==' },
            { value: 'neq', label: 'برابر نیست با', shortEn: '!=' },
            { value: 'gt', label: 'بزرگ‌تر از', shortEn: '>' },
            { value: 'gte', label: 'بزرگ‌تر یا مساوی', shortEn: '>=' },
            { value: 'lt', label: 'کوچک‌تر از', shortEn: '<' },
            { value: 'lte', label: 'کوچک‌تر یا مساوی', shortEn: '<=' },
        ],
    },
    {
        type: 'datetime',
        label: 'Date & Time',
        faLabel: 'تاریخ و زمان',
        badge: '📅',
        icon: 'calendar',
        operators: [
            { value: 'exists', label: 'وجود دارد', shortEn: 'exists', unary: true },
            { value: 'not_exists', label: 'وجود ندارد', shortEn: 'not exists', unary: true },
            { value: 'empty', label: 'خالی است', shortEn: 'is empty', unary: true },
            { value: 'not_empty', label: 'خالی نیست', shortEn: 'not empty', unary: true },
            { value: 'eq', label: 'برابر است با', shortEn: 'equal' },
            { value: 'neq', label: 'برابر نیست با', shortEn: 'not equal' },
            { value: 'date_after', label: 'بعد از تاریخ', shortEn: 'after' },
            { value: 'date_before', label: 'قبل از تاریخ', shortEn: 'before' },
            { value: 'date_after_or_eq', label: 'بعد یا هم‌زمان با', shortEn: 'after / eq' },
            { value: 'date_before_or_eq', label: 'قبل یا هم‌زمان با', shortEn: 'before / eq' },
        ],
    },
    {
        type: 'boolean',
        label: 'Boolean',
        faLabel: 'بولین',
        badge: '✓',
        operators: [
            { value: 'exists', label: 'وجود دارد', shortEn: 'exists', unary: true },
            { value: 'not_exists', label: 'وجود ندارد', shortEn: 'not exists', unary: true },
            { value: 'empty', label: 'خالی است', shortEn: 'is empty', unary: true },
            { value: 'not_empty', label: 'خالی نیست', shortEn: 'not empty', unary: true },
            { value: 'is_true', label: 'درست است', shortEn: 'is true', unary: true },
            { value: 'is_false', label: 'نادرست است', shortEn: 'is false', unary: true },
            { value: 'eq', label: 'برابر است با', shortEn: 'equal' },
            { value: 'neq', label: 'برابر نیست با', shortEn: 'not equal' },
        ],
    },
    {
        type: 'array',
        label: 'Array',
        faLabel: 'آرایه / لیست',
        badge: '[]',
        operators: [
            { value: 'exists', label: 'وجود دارد', shortEn: 'exists', unary: true },
            { value: 'not_exists', label: 'وجود ندارد', shortEn: 'not exists', unary: true },
            { value: 'empty', label: 'خالی است', shortEn: 'is empty', unary: true },
            { value: 'not_empty', label: 'خالی نیست', shortEn: 'not empty', unary: true },
            { value: 'array_contains', label: 'شامل آیتم است', shortEn: 'contains' },
            { value: 'array_not_contains', label: 'شامل آیتم نیست', shortEn: 'not contains' },
            { value: 'array_length_eq', label: 'تعداد برابر با', shortEn: 'len ==' },
            { value: 'array_length_neq', label: 'تعداد مخالف با', shortEn: 'len !=' },
            { value: 'array_length_gt', label: 'تعداد بیشتر از', shortEn: 'len >' },
            { value: 'array_length_gte', label: 'تعداد بیشتر یا مساوی', shortEn: 'len >=' },
            { value: 'array_length_lt', label: 'تعداد کمتر از', shortEn: 'len <' },
            { value: 'array_length_lte', label: 'تعداد کمتر یا مساوی', shortEn: 'len <=' },
        ],
    },
    {
        type: 'object',
        label: 'Object',
        faLabel: 'آبجکت',
        badge: '{}',
        operators: [
            { value: 'exists', label: 'وجود دارد', shortEn: 'exists', unary: true },
            { value: 'not_exists', label: 'وجود ندارد', shortEn: 'not exists', unary: true },
            { value: 'empty', label: 'خالی است', shortEn: 'is empty', unary: true },
            { value: 'not_empty', label: 'خالی نیست', shortEn: 'not empty', unary: true },
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

export const getSecondFieldPlaceholder = (dataType: ConditionDataType, operator: string): string => {
    if (operator.startsWith('array_length_')) return 'مقدار دوم (مثلاً: 3)';
    if (dataType === 'number') return 'مقدار دوم (مثلاً: 100)';
    if (dataType === 'datetime') return 'مقدار دوم (مثلاً: 2026-11-08)';
    if (dataType === 'boolean') return 'true یا false';
    return 'مقدار دوم (Value 2)';
};

export function moveArrayItem<T>(list: T[], fromIndex: number, toIndex: number): T[] | null {
    if (toIndex < 0 || toIndex >= list.length || fromIndex === toIndex) {
        return null;
    }
    const copy = [...list];
    const [moved] = copy.splice(fromIndex, 1);
    copy.splice(toIndex, 0, moved);
    return copy;
}

type ConditionOperatorSelectProps = {
    dataType: ConditionDataType;
    operator: string;
    onChange: (next: { dataType: ConditionDataType; operator: string }) => void;
};

type MenuPlacement = {
    top?: number;
    bottom?: number;
    left: number;
    width: number;
    maxHeight: number;
    openUpward: boolean;
};

const DESIRED_HEIGHT = 284;
const MENU_MIN_WIDTH = 390;
const VIEWPORT_MARGIN = 16;

export function ConditionOperatorSelect({
                                            dataType,
                                            operator,
                                            onChange,
                                        }: ConditionOperatorSelectProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [activeType, setActiveType] = useState<ConditionDataType>(dataType);
    const [placement, setPlacement] = useState<MenuPlacement | null>(null);

    const triggerRef = useRef<HTMLButtonElement>(null);
    const menuRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setActiveType(dataType);
    }, [dataType]);

    const updatePlacement = () => {
        const btn = triggerRef.current;
        if (!btn) return;

        const rect = btn.getBoundingClientRect();
        const scrollParent = btn.closest('.overflow-y-auto');
        const boundsTop = scrollParent
            ? Math.max(VIEWPORT_MARGIN, scrollParent.getBoundingClientRect().top + 8)
            : VIEWPORT_MARGIN;
        const boundsBottom = scrollParent
            ? Math.min(
                window.innerHeight - VIEWPORT_MARGIN,
                scrollParent.getBoundingClientRect().bottom - 8,
            )
            : window.innerHeight - VIEWPORT_MARGIN;

        const spaceBelow = boundsBottom - rect.bottom - 6;
        const spaceAbove = rect.top - boundsTop - 6;

        const openUpward = spaceBelow < 210 && spaceAbove > spaceBelow;
        const availableSpace = openUpward ? spaceAbove : spaceBelow;
        const maxHeight = Math.max(190, Math.min(DESIRED_HEIGHT, availableSpace));

        const menuWidth = Math.min(
            window.innerWidth - VIEWPORT_MARGIN * 2,
            Math.max(rect.width, MENU_MIN_WIDTH),
        );
        const clampedLeft = Math.max(
            VIEWPORT_MARGIN,
            Math.min(rect.left, window.innerWidth - menuWidth - VIEWPORT_MARGIN),
        );

        setPlacement({
            top: openUpward ? undefined : rect.bottom + 6,
            bottom: openUpward ? window.innerHeight - rect.top + 6 : undefined,
            left: clampedLeft,
            width: menuWidth,
            maxHeight,
            openUpward,
        });
    };

    useLayoutEffect(() => {
        if (!isOpen) return;
        updatePlacement();
    }, [isOpen]);

    useEffect(() => {
        if (!isOpen) return;

        const handleOutside = (event: MouseEvent) => {
            const target = event.target as Node;
            if (
                triggerRef.current &&
                !triggerRef.current.contains(target) &&
                menuRef.current &&
                !menuRef.current.contains(target)
            ) {
                setIsOpen(false);
            }
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        };

        const handleScrollOrResize = (event: Event) => {
            if (menuRef.current && event.target instanceof Node && menuRef.current.contains(event.target)) {
                return;
            }
            updatePlacement();
        };

        document.addEventListener('mousedown', handleOutside);
        document.addEventListener('keydown', handleKeyDown);
        window.addEventListener('resize', handleScrollOrResize);
        window.addEventListener('scroll', handleScrollOrResize, true);

        return () => {
            document.removeEventListener('mousedown', handleOutside);
            document.removeEventListener('keydown', handleKeyDown);
            window.removeEventListener('resize', handleScrollOrResize);
            window.removeEventListener('scroll', handleScrollOrResize, true);
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
        if (!isOpen) {
            setActiveType(dataType);
            updatePlacement();
        }
        setIsOpen((prev) => !prev);
    };

    return (
        <div className="relative w-full select-none" dir="rtl">
            <button
                ref={triggerRef}
                type="button"
                onClick={toggleOpen}
                className={`flex h-10.5 w-full items-center justify-between gap-2 rounded-xl border bg-field px-3 text-xs font-medium text-content-main shadow-xs transition hover:border-content-muted/40 focus:outline-none ${
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
                    {currentOperator?.shortEn && (
                        <span
                            dir="ltr"
                            className="hidden shrink-0 rounded bg-line-border/50 px-1.5 py-0.5 font-mono text-[10px] text-content-muted sm:inline-block"
                        >
                            {currentOperator.shortEn}
                        </span>
                    )}
                </span>

                <Icon
                    name="chevron-down"
                    className={`size-4 shrink-0 text-content-muted transition-transform duration-200 ${
                        isOpen
                            ? placement?.openUpward
                                ? 'rotate-0 text-primary'
                                : 'rotate-180 text-primary'
                            : ''
                    }`}
                />
            </button>

            {isOpen &&
                placement &&
                createPortal(
                    <div
                        ref={menuRef}
                        dir="rtl"
                        style={{
                            position: 'fixed',
                            top: placement.top,
                            bottom: placement.bottom,
                            left: placement.left,
                            width: placement.width,
                            maxHeight: placement.maxHeight,
                        }}
                        className="z-100 flex overflow-hidden rounded-2xl border border-line-border bg-surface-raised shadow-2xl backdrop-blur-xl animate-in fade-in zoom-in-95 duration-100"
                    >
                        <div className="custom-scrollbar w-38 shrink-0 space-y-0.5 overflow-y-auto border-l border-line-border/70 bg-surface-base/70 p-1.5 dark:bg-surface-card/60">
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
                                                ? 'bg-primary font-bold text-white shadow-2xs'
                                                : 'font-medium text-content-main hover:bg-line-border/50'
                                        }`}
                                    >
                                        <span className="flex min-w-0 items-center gap-2">
                                            <span
                                                dir="ltr"
                                                className={`inline-flex w-5 shrink-0 justify-center font-mono text-[11px] font-bold ${
                                                    isActive ? 'text-white' : 'text-content-muted'
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
                                        <Icon
                                            name="chevron-left"
                                            className={`size-3.5 shrink-0 ${
                                                isActive ? 'text-white' : 'opacity-50'
                                            }`}
                                        />
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
                                                ? 'bg-primary/12 font-bold text-primary'
                                                : 'font-medium text-content-main hover:bg-line-border/50 hover:text-primary'
                                        }`}
                                    >
                                        <span className="truncate">{op.label}</span>
                                        <span className="flex shrink-0 items-center gap-1.5">
                                            <span
                                                dir="ltr"
                                                className={`font-mono text-[10px] ${
                                                    isSelected ? 'text-primary' : 'text-content-muted/75'
                                                }`}
                                            >
                                                {op.shortEn}
                                            </span>
                                            {isSelected && (
                                                <Icon name="check" className="size-3.5 shrink-0 text-primary" />
                                            )}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>,
                    document.body,
                )}
        </div>
    );
}

type SharedRuleCardProps = {
    idPrefix: string;
    rule: {
        id: string;
        data_type: ConditionDataType;
        field: string;
        operator: string;
        value: string;
    };
    index: number;
    total: number;
    accentColor?: 'primary' | 'orange';
    badgeText?: string;
    incomingData?: unknown;
    isDragging: boolean;
    isDragOver: boolean;
    onDragStart: () => void;
    onDragOver: () => void;
    onDrop: () => void;
    onDragEnd: () => void;
    onMoveUp: () => void;
    onMoveDown: () => void;
    onRemove: () => void;
    onUpdate: (patch: {
        data_type?: ConditionDataType;
        field?: string;
        operator?: string;
        value?: string;
    }) => void;
    footer?: ReactNode;
};

export function RuleCardShell({
                                  idPrefix,
                                  rule,
                                  index,
                                  total,
                                  accentColor = 'primary',
                                  badgeText,
                                  incomingData,
                                  isDragging,
                                  isDragOver,
                                  onDragStart,
                                  onDragOver,
                                  onDrop,
                                  onDragEnd,
                                  onMoveUp,
                                  onMoveDown,
                                  onRemove,
                                  onUpdate,
                                  footer,
                              }: SharedRuleCardProps) {
    const hideSecondValue = isUnaryOperator(rule.operator);
    const isOrange = accentColor === 'orange';

    return (
        <div
            onDragOver={(e) => {
                e.preventDefault();
                onDragOver();
            }}
            onDrop={(e) => {
                e.preventDefault();
                onDrop();
            }}
            className={`group relative rounded-2xl border bg-surface-card p-3 shadow-2xs transition-all duration-150 ${
                isDragging
                    ? 'scale-[0.99] border-primary/50 opacity-45'
                    : isDragOver
                        ? 'border-primary ring-2 ring-primary/20'
                        : 'border-line-border/90 hover:border-line-border'
            }`}
        >
            <div className="mb-2 flex items-center justify-between gap-2">
                <div className="flex min-w-0 items-center gap-1.5">
                    {total > 1 && (
                        <span
                            draggable
                            title="برای جابجایی ترتیب بکشید"
                            onDragStart={(e) => {
                                e.dataTransfer.effectAllowed = 'move';
                                onDragStart();
                            }}
                            onDragEnd={onDragEnd}
                            className="flex size-6 shrink-0 cursor-grab items-center justify-center rounded-md text-content-muted/50 transition hover:bg-line-border/50 hover:text-content-main active:cursor-grabbing"
                        >
                            <Icon name="grip-vertical" className="size-3.5" />
                        </span>
                    )}

                    <span
                        className={`flex size-5 shrink-0 items-center justify-center rounded-full font-mono text-[10px] font-bold ${
                            isOrange
                                ? 'bg-orange-500/15 text-orange-600 dark:text-orange-400'
                                : 'bg-primary/10 text-primary'
                        }`}
                    >
                        {index + 1}
                    </span>

                    {badgeText ? (
                        <span
                            dir="auto"
                            className="truncate rounded-md border border-orange-500/30 bg-orange-500/10 px-2 py-0.5 text-[10px] font-bold text-orange-600 dark:text-orange-400"
                        >
                            {badgeText}
                        </span>
                    ) : (
                        <span className="text-xs font-bold text-content-main">
                            شرط {index + 1}
                        </span>
                    )}
                </div>

                <div className="flex shrink-0 items-center gap-0.5">
                    {total > 1 && (
                        <>
                            <button
                                type="button"
                                disabled={index === 0}
                                onClick={onMoveUp}
                                title="انتقال به بالا"
                                className="flex size-6 items-center justify-center rounded-md text-content-muted transition hover:bg-line-border/50 hover:text-content-main disabled:opacity-25"
                            >
                                <Icon name="chevron-down" className="size-3.5 rotate-180" />
                            </button>
                            <button
                                type="button"
                                disabled={index === total - 1}
                                onClick={onMoveDown}
                                title="انتقال به پایین"
                                className="flex size-6 items-center justify-center rounded-md text-content-muted transition hover:bg-line-border/50 hover:text-content-main disabled:opacity-25"
                            >
                                <Icon name="chevron-down" className="size-3.5" />
                            </button>
                        </>
                    )}

                    <button
                        type="button"
                        onClick={onRemove}
                        aria-label="حذف"
                        className="flex size-6 items-center justify-center rounded-md text-content-muted transition hover:bg-primary/10 hover:text-primary"
                    >
                        <Icon name="trash" className="size-3.5" />
                    </button>
                </div>
            </div>

            <div className="space-y-2">
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:items-end">
                    <div className="min-w-0">
                        <ConfigFieldInput
                            id={`${idPrefix}-field-${rule.id}`}
                            field={{
                                type: 'text',
                                placeholder: 'مقدار اول (Value 1)',
                            }}
                            value={rule.field}
                            incomingData={incomingData}
                            onChange={(val) => onUpdate({ field: String(val ?? '') })}
                        />
                    </div>

                    <div className="min-w-0">
                        <ConditionOperatorSelect
                            dataType={rule.data_type}
                            operator={rule.operator}
                            onChange={({ dataType, operator }) =>
                                onUpdate({
                                    data_type: dataType,
                                    operator,
                                    value: isUnaryOperator(operator) ? '' : rule.value,
                                })
                            }
                        />
                    </div>
                </div>

                {!hideSecondValue && (
                    <ConfigFieldInput
                        id={`${idPrefix}-val-${rule.id}`}
                        field={{
                            type: 'text',
                            placeholder: getSecondFieldPlaceholder(rule.data_type, rule.operator),
                        }}
                        value={rule.value}
                        incomingData={incomingData}
                        onChange={(val) => onUpdate({ value: String(val ?? '') })}
                    />
                )}

                {footer}
            </div>
        </div>
    );
}
