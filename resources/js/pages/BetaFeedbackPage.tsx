import { useState, type FormEvent } from 'react';
import { AppShell } from '../components/AppShell';
import { ApiError, apiRequest } from '../lib/api';

type FeedbackCategory = 'bug' | 'suggestion' | 'usability' | 'other';

export function BetaFeedbackPage() {
    const [category, setCategory] = useState<FeedbackCategory>('other');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setError('');
        setConfirmation('');
        setIsSubmitting(true);

        try {
            const response = await apiRequest<{ message: string }>('/api/v1/beta-feedback', {
                method: 'POST',
                body: JSON.stringify({ category, message }),
            });
            setConfirmation(response.message);
            setMessage('');
            setCategory('other');
        } catch (exception) {
            setError(exception instanceof ApiError ? exception.errors.message?.[0] ?? exception.message : 'Feedback could not be sent. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return <AppShell>
        <div className="mx-auto max-w-3xl">
            <p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">LUMA beta</p>
            <h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Help shape what comes next.</h1>
            <p className="mt-4 max-w-2xl text-base leading-7 text-stone-600">Tell us what felt useful, what was confusing, or what would make you come back. Please don’t include passwords, payment details, or other sensitive information.</p>

            <form onSubmit={submit} className="mt-8 grid gap-5 rounded-[1.75rem] border border-black/10 bg-paper p-6 shadow-sm sm:p-8">
                <div>
                    <label htmlFor="feedback-category" className="text-sm font-semibold">Category <span className="font-normal text-stone-500">(optional)</span></label>
                    <select id="feedback-category" value={category} onChange={(event) => setCategory(event.target.value as FeedbackCategory)} className="mt-2 h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-sm">
                        <option value="other">General feedback</option>
                        <option value="bug">Bug</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="usability">Usability</option>
                    </select>
                </div>
                <div>
                    <label htmlFor="feedback-message" className="text-sm font-semibold">Your feedback</label>
                    <textarea id="feedback-message" required maxLength={4000} rows={8} value={message} onChange={(event) => setMessage(event.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm leading-6" placeholder="What should the LUMA team know?" />
                    <p className="mt-2 text-right text-xs text-stone-500">{message.length} / 4,000</p>
                </div>
                {error && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
                {confirmation && <p role="status" className="rounded-xl border border-moss/20 bg-acid/20 px-4 py-3 text-sm font-medium text-moss">{confirmation}</p>}
                <button disabled={isSubmitting} className="h-12 rounded-xl bg-ink px-6 font-semibold text-white transition hover:bg-moss disabled:cursor-wait disabled:opacity-60">{isSubmitting ? 'Sending…' : 'Send feedback'}</button>
            </form>
        </div>
    </AppShell>;
}
