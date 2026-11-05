import { useCallback, useEffect, useState } from 'react';
import { apiRequest } from '../lib/api';
import type { PaginatedResponse } from '../types';

type Feedback = { id: number; category: string; message: string; created_at: string; user: { name: string; email: string } };

export function BetaFeedbackManager() {
    const [feedback, setFeedback] = useState<PaginatedResponse<Feedback> | null>(null);
    const [page, setPage] = useState(1);
    const [error, setError] = useState('');

    const load = useCallback(() => {
        setError('');
        apiRequest<PaginatedResponse<Feedback>>(`/api/v1/admin/beta-feedback?page=${page}&per_page=20`)
            .then(setFeedback)
            .catch(() => setError('Beta feedback could not be loaded.'));
    }, [page]);

    useEffect(() => load(), [load]);

    return <section className="mt-6 rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-8" aria-labelledby="feedback-heading">
        <p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Beta feedback</p>
        <h2 id="feedback-heading" className="mt-2 text-2xl font-semibold">What beta users are telling us</h2>
        <p className="mt-2 text-sm text-stone-600">Newest submissions first. Feedback may contain user-entered text.</p>
        {error && <p role="alert" className="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
        {!feedback && !error && <p className="mt-6 text-sm text-stone-500">Loading feedback…</p>}
        {feedback && <>
            <div className="mt-6 grid gap-3">
                {feedback.data.map((item) => <article key={item.id} className="rounded-2xl border border-black/10 bg-white p-5">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div><span className="rounded-full bg-acid/40 px-2.5 py-1 text-xs font-bold capitalize">{item.category}</span><strong className="ml-3 text-sm">{item.user.name}</strong><span className="ml-2 text-xs text-stone-500">{item.user.email}</span></div>
                        <time className="text-xs text-stone-500" dateTime={item.created_at}>{new Date(item.created_at).toLocaleString()}</time>
                    </div>
                    <p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-stone-700">{item.message}</p>
                </article>)}
                {feedback.data.length === 0 && <p className="rounded-xl border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">No beta feedback has been submitted yet.</p>}
            </div>
            <div className="mt-5 flex items-center justify-between gap-4 text-sm"><span className="text-stone-500">{feedback.meta.total} submissions</span><div className="flex items-center gap-2"><button type="button" disabled={feedback.meta.current_page <= 1} onClick={() => setPage((value) => value - 1)} className="rounded-lg border border-stone-300 px-3 py-2 font-semibold disabled:opacity-40">Previous</button><span>{feedback.meta.current_page} / {feedback.meta.last_page}</span><button type="button" disabled={feedback.meta.current_page >= feedback.meta.last_page} onClick={() => setPage((value) => value + 1)} className="rounded-lg border border-stone-300 px-3 py-2 font-semibold disabled:opacity-40">Next</button></div></div>
        </>}
    </section>;
}
