import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { AuthLayout } from '../components/AuthLayout';
import { FormField } from '../components/FormField';
import { useAuth } from '../context/AuthContext';
import { ApiError } from '../lib/api';

export function RegisterPage() {
    const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { register } = useAuth();
    const navigate = useNavigate();
    const update = (field: keyof typeof form, value: string) => setForm((current) => ({ ...current, [field]: value }));

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setErrors({});
        setIsSubmitting(true);
        try {
            await register(form);
            navigate('/dashboard');
        } catch (error) {
            setErrors(error instanceof ApiError ? error.errors : { email: ['Unable to create your account right now.'] });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <AuthLayout eyebrow="Start simply" title="Create your account" copy="Build a steady rhythm of understanding and applying God’s Word.">
            <form onSubmit={submit} className="grid gap-4">
                <FormField id="name" label="Name" autoComplete="name" value={form.name} onChange={(event) => update('name', event.target.value)} error={errors.name?.[0]} required />
                <FormField id="email" label="Email address" type="email" autoComplete="email" value={form.email} onChange={(event) => update('email', event.target.value)} error={errors.email?.[0]} required />
                <FormField id="password" label="Password" type="password" autoComplete="new-password" value={form.password} onChange={(event) => update('password', event.target.value)} error={errors.password?.[0]} required />
                <FormField id="password_confirmation" label="Confirm password" type="password" autoComplete="new-password" value={form.password_confirmation} onChange={(event) => update('password_confirmation', event.target.value)} required />
                <button disabled={isSubmitting} className="mt-2 h-12 rounded-xl bg-ink px-5 font-semibold text-white transition hover:bg-moss disabled:cursor-wait disabled:opacity-60">{isSubmitting ? 'Creating account…' : 'Create account'}</button>
            </form>
            <p className="mt-7 text-center text-sm text-stone-600">Already have an account? <Link to="/login" className="font-semibold text-ink underline underline-offset-4">Sign in</Link></p>
        </AuthLayout>
    );
}
