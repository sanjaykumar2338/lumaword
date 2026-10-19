import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { AppShell } from '../components/AppShell';
import { DevotionalView } from '../components/DevotionalView';
import { SermonStatusBadge } from '../components/SermonStatusBadge';
import { SermonSubmissionForm } from '../components/SermonSubmissionForm';
import { useAuth } from '../context/AuthContext';
import { apiRequest } from '../lib/api';
import type { Devotional, PaginatedResponse, ResourceResponse, Sermon, UserReadingPlan } from '../types';

export function DashboardPage() {
    const { user } = useAuth();
    const [recent, setRecent] = useState<Sermon[]>([]);
    const [today, setToday] = useState<Devotional | null>(null);
    const [readingPlans, setReadingPlans] = useState<UserReadingPlan[]>([]);
    const [habitsLoading, setHabitsLoading] = useState(true);
    const [habitsError, setHabitsError] = useState(false);

    useEffect(() => {
        apiRequest<PaginatedResponse<Sermon>>('/api/v1/sermons?per_page=4')
            .then((response) => setRecent(response.data))
            .catch(() => setRecent([]));
    }, []);

    useEffect(() => {
        Promise.all([
            apiRequest<ResourceResponse<Devotional>>('/api/v1/devotionals/today'),
            apiRequest<{ data: UserReadingPlan[] }>('/api/v1/reading-plan-enrollments'),
        ]).then(([daily, enrollments]) => {
            setToday(daily.data);
            setReadingPlans(enrollments.data);
        }).catch(() => setHabitsError(true)).finally(() => setHabitsLoading(false));
    }, []);

    return (
        <AppShell>
            <header className="flex flex-col gap-4 border-b border-black/10 pb-8 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Luma</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Welcome, {user?.name.split(' ')[0] ?? 'friend'}.</h1></div>
                <Link to="/sermons" className="text-sm font-semibold text-moss hover:underline">Open sermon library</Link>
            </header>

            <section className="mt-8">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Today with God</p><h2 className="mt-2 text-2xl font-semibold tracking-[-0.04em]">Keep your study rhythm.</h2></div><div className="flex gap-4 text-sm font-semibold text-moss"><Link to="/devotionals" className="hover:underline">All devotionals</Link><Link to="/reading-plans" className="hover:underline">Reading plans</Link></div></div>
                {habitsLoading ? <div className="mt-4 grid min-h-40 place-items-center rounded-[1.75rem] border border-dashed border-black/15 text-sm text-stone-500">Loading today’s study…</div> : habitsError ? <div role="alert" className="mt-4 rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">Today’s devotional and reading progress could not be loaded. Your sermon tools are still available below.</div> : (
                    <div className="mt-4 grid gap-5 xl:grid-cols-[1.25fr_0.75fr]">
                        {today && <div><DevotionalView devotional={today} compact /><Link to="/devotionals" className="mt-3 inline-block text-sm font-semibold text-moss hover:underline">Read application and prayer →</Link></div>}
                        <div className="rounded-[1.75rem] bg-ink p-6 text-white sm:p-8">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-acid">Active reading plans</p>
                            <h3 className="mt-2 text-2xl font-semibold">Continue reading</h3>
                            <div className="mt-5 grid gap-3">
                                {readingPlans.filter((enrollment) => !enrollment.completed_at).length === 0 ? <div className="rounded-2xl border border-dashed border-white/15 p-5"><p className="text-sm leading-6 text-stone-400">Choose a 7, 14, or 30-day plan to begin tracking your reading.</p><Link to="/reading-plans" className="mt-3 inline-block text-sm font-semibold text-acid">Browse starter plans →</Link></div> : readingPlans.filter((enrollment) => !enrollment.completed_at).map((enrollment) => {
                                    const percent = Math.round((enrollment.completed_days / enrollment.plan.duration_days) * 100);
                                    return <Link key={enrollment.id} to="/reading-plans" className="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-acid/40"><div className="flex justify-between gap-3"><span className="font-semibold">{enrollment.plan.title}</span><span className="text-xs text-stone-400">{enrollment.completed_days}/{enrollment.plan.duration_days}</span></div><div className="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10"><div className="h-full bg-acid" style={{ width: `${percent}%` }} /></div></Link>;
                                })}
                            </div>
                        </div>
                    </div>
                )}
            </section>

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
