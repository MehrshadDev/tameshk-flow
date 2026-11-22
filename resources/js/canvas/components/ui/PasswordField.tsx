import { useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';

type PasswordFieldProps = {
    id?: string;
    name?: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    autoFocus?: boolean;
    disabled?: boolean;
    className?: string;
};

export function PasswordField({
                                  id,
                                  name,
                                  value,
                                  onChange,
                                  placeholder = '',
                                  autoFocus = false,
                                  disabled = false,
                                  className = '',
                              }: PasswordFieldProps) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="relative w-full" dir="ltr">
            <input
                id={id}
                name={name}
                type={visible ? 'text' : 'password'}
                autoFocus={autoFocus}
                disabled={disabled}
                autoComplete="new-password"
                data-lpignore="true"
                data-1p-ignore="true"
                spellCheck={false}
                value={value}
                placeholder={placeholder}
                onChange={(e) => onChange(e.target.value)}
                className={`w-full rounded-xl border border-field-border bg-field py-2.5 pl-3.5 pr-10 font-mono text-sm text-content-main transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/15 disabled:opacity-50 ${className}`}
            />

            <button
                type="button"
                tabIndex={-1}
                disabled={disabled}
                onClick={() => setVisible((prev) => !prev)}
                aria-label={visible ? 'مخفی‌سازی' : 'نمایش'}
                className="absolute right-2.5 top-1/2 flex size-7 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main disabled:opacity-50"
            >
                <Icon name={visible ? 'eye-closed' : 'eye'} className="size-4" />
            </button>
        </div>
    );
}
