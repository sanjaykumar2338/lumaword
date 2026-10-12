import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { AppShell } from '../components/AppShell';
import { SermonStatusBadge } from '../components/SermonStatusBadge';
import { SermonSubmissionForm } from '../components/SermonSubmissionForm';
import { useAuth } from '../context/AuthContext';
import { apiRequest } from '../lib/api';
import type { PaginatedResponse, Sermon } from '../types';

export function DashboardPage() {
    const { user } = useAuth();
    const [recent, setRecent] = useState<Sermon[]>([]);

    useEffect(() => {
        apiRequest<PaginatedResponse<Sermon>>('/api/v1/sermons?per_page=4')
            .then((response) => setRecent(response.data))
            .catch(() => setRecent([]));
    }, []);

    return (
        <AppShell>
            <header className="flex flex-col gap-4 border-b border-black/10 pb-8 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Your LumaWord</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Welcome, {user?.name.split(' ')[0] ?? 'friend'}.</h1></div>
                <Link to="/sermons" className="text-sm font-semibold text-moss hover:underline">Open sermon library</Link>
            </header>

            <section className="mt-8 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <div>
                    <div className="mb-4"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Start here</p><h2 className="mt-2 text-2xl font-semibold tracking-[-0.04em]">Bring a sermon into focus.</h2></div>
                    <SermonSubmissionForm compact />
                </div>

                <div className="rounded-[1.75rem] bg-ink p-6 text-white sm:p-8">
                    <div className="flex items-center justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.2em] text-acid">Recent sermons</p><h2 className="mt-2 text-2xl font-semibold">Continue studying</h2></div><span className="text-xs text-stone-500">{recent.length} shown</span></div>
                    <div className="mt-6 grid gap-3">
                        {recent.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-white/15 p-6 text-sm leading-6 text-stone-400">Your first submitted sermon will appear here with its processing status.</div>
                        ) : recent.map((sermon) => (
                            <Link key={sermon.id} to={`/sermons/${sermon.id}`} className="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-acid/40 hover:bg-white/10">
                                <div className="flex items-start justify-between gap-3"><h3 className="font-semibold">{sermon.title}</h3><SermonStatusBadge status={sermon.processing_status} /></div>
                                <p className="mt-2 text-xs text-stone-500">Added {new Date(sermon.created_at).toLocaleDateString()}</p>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <section className="mt-8 grid gap-4 sm:grid-cols-3">
                {[
                    ['1', 'Submit', 'Use a link, transcript, or authorized media file.'],
                    ['2', 'Process', 'Private queued work creates a transcript and structured notes.'],
                    ['3', 'Study', 'Review the main idea, outline, references, applications, and questions.'],
                ].map(([number, title, copy]) => (
                    <article key={number} className="rounded-2xl border border-black/10 bg-paper p-6"><span className="text-xs font-bold text-moss">0{number}</span><h3 className="mt-5 text-lg font-semibold">{title}</h3><p className="mt-2 text-sm leading-6 text-stone-600">{copy}</p></article>
                ))}
            </section>
        </AppShell>
    );
}
