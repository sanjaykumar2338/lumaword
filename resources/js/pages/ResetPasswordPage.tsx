import { useState, type FormEvent } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { AuthLayout } from '../components/AuthLayout';
import { FormField } from '../components/FormField';
import { ApiError, apiRequest } from '../lib/api';
import type { MessageResponse } from '../types';

export function ResetPasswordPage() {
    const { token = '' } = useParams();
    const [search] = useSearchParams();
    const [form, setForm] = useState({ email: search.get('email') ?? '', password: '', password_confirmation: '' });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const navigate = useNavigate();
    const update = (field: keyof typeof form, value: string) => setForm((current) => ({ ...current, [field]: value }));

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setErrors({});
        setIsSubmitting(true);
        try {
            await apiRequest<MessageResponse>('/api/v1/auth/reset-password', { method: 'POST', body: JSON.stringify({ ...form, token }) });
            navigate('/login', { replace: true });
        } catch (error) {
            setErrors(error instanceof ApiError ? error.errors : { email: ['Unable to reset your password.'] });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <AuthLayout eyebrow="Choose a new password" title="Secure your account" copy="Use a strong password you do not reuse elsewhere.">
            <form onSubmit={submit} className="grid gap-4">
                <FormField id="email" label="Email address" type="email" value={form.email} onChange={(event) => update('email', event.target.value)} error={errors.email?.[0]} required />
                <FormField id="password" label="New password" type="password" autoComplete="new-password" value={form.password} onChange={(event) => update('password', event.target.value)} error={errors.password?.[0]} required />
                <FormField id="password_confirmation" label="Confirm new password" type="password" autoComplete="new-password" value={form.password_confirmation} onChange={(event) => update('password_confirmation', event.target.value)} required />
                <button disabled={isSubmitting} className="mt-2 h-12 rounded-xl bg-ink px-5 font-semibold text-white transition hover:bg-moss disabled:opacity-60">{isSubmitting ? 'Resetting…' : 'Reset password'}</button>
            </form>
        </AuthLayout>
    );
}
