import { useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { AuthLayout } from '../components/AuthLayout';
import { FormField } from '../components/FormField';
import { ApiError, apiRequest } from '../lib/api';
import type { MessageResponse } from '../types';

export function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
    const [error, setError] = useState<string>();
    const [message, setMessage] = useState<string>();
    const [isSubmitting, setIsSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setError(undefined);
        setIsSubmitting(true);
        try {
            const response = await apiRequest<MessageResponse>('/api/v1/auth/forgot-password', { method: 'POST', body: JSON.stringify({ email }) });
            setMessage(response.message);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.errors.email?.[0] ?? caught.message : 'Unable to send a reset link.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <AuthLayout eyebrow="Account recovery" title="Reset your password" copy="Enter your email and we’ll send secure reset instructions if an account is found.">
            {message ? (
                <div className="rounded-2xl border border-lime-700/20 bg-lime-100 p-5 text-sm leading-6 text-moss">{message}</div>
            ) : (
                <form onSubmit={submit} className="grid gap-5">
                    <FormField id="email" label="Email address" type="email" autoComplete="email" value={email} onChange={(event) => setEmail(event.target.value)} error={error} required />
                    <button disabled={isSubmitting} className="h-12 rounded-xl bg-ink px-5 font-semibold text-white transition hover:bg-moss disabled:opacity-60">{isSubmitting ? 'Sending…' : 'Send reset link'}</button>
                </form>
            )}
            <p className="mt-7 text-center text-sm"><Link to="/login" className="font-semibold underline underline-offset-4">Return to sign in</Link></p>
        </AuthLayout>
    );
}
