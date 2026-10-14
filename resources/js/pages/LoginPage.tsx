import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { AuthLayout } from '../components/AuthLayout';
import { FormField } from '../components/FormField';
import { useAuth } from '../context/AuthContext';
import { ApiError } from '../lib/api';

export function LoginPage() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { login } = useAuth();
    const navigate = useNavigate();

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setErrors({});
        setIsSubmitting(true);
        try {
            await login({ email, password });
            navigate('/dashboard');
        } catch (error) {
            setErrors(error instanceof ApiError ? error.errors : { email: ['Unable to sign in right now.'] });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <AuthLayout eyebrow="Welcome back" title="Continue your journey" copy="Sign in to return to your Luma dashboard.">
            <form onSubmit={submit} className="grid gap-5">
                <FormField id="email" label="Email address" type="email" autoComplete="email" value={email} onChange={(event) => setEmail(event.target.value)} error={errors.email?.[0]} required />
                <FormField id="password" label="Password" type="password" autoComplete="current-password" value={password} onChange={(event) => setPassword(event.target.value)} error={errors.password?.[0]} required />
                <div className="flex justify-end"><Link to="/forgot-password" className="text-sm font-medium underline decoration-stone-300 underline-offset-4 hover:decoration-ink">Forgot password?</Link></div>
                <button disabled={isSubmitting} className="h-12 rounded-xl bg-ink px-5 font-semibold text-white transition hover:bg-moss disabled:cursor-wait disabled:opacity-60">{isSubmitting ? 'Signing in…' : 'Sign in'}</button>
            </form>
            <p className="mt-7 text-center text-sm text-stone-600">New to Luma? <Link to="/register" className="font-semibold text-ink underline underline-offset-4">Create an account</Link></p>
        </AuthLayout>
    );
}
