import type { InputHTMLAttributes } from 'react';

type FormFieldProps = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    error?: string;
};

export function FormField({ label, error, id, ...props }: FormFieldProps) {
    return (
        <label htmlFor={id} className="grid gap-2 text-sm font-medium text-stone-700">
            {label}
            <input
                id={id}
                {...props}
                aria-invalid={Boolean(error)}
                aria-describedby={error ? `${id}-error` : undefined}
                className="h-12 rounded-xl border border-stone-300 bg-white px-4 text-base text-ink shadow-sm transition placeholder:text-stone-400 hover:border-stone-400 focus:border-ink focus:ring-0"
            />
            {error && <span id={`${id}-error`} className="text-xs font-medium text-red-700">{error}</span>}
        </label>
    );
}
