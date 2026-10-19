import { useEffect, useMemo, useState } from 'react';
import { AppShell } from '../components/AppShell';
import { ApiError, apiRequest } from '../lib/api';
import type { ReadingPlan, ResourceResponse, UserReadingPlan } from '../types';

export function ReadingPlansPage() {
    const [plans, setPlans] = useState<ReadingPlan[]>([]);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [openDayId, setOpenDayId] = useState<number | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [busyDayId, setBusyDayId] = useState<number | null>(null);
    const [isStarting, setIsStarting] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        apiRequest<{ data: ReadingPlan[] }>('/api/v1/reading-plans')
            .then((response) => {
                setPlans(response.data);
                setSelectedId(response.data.find((plan) => plan.enrollment && !plan.enrollment.completed_at)?.id ?? response.data[0]?.id ?? null);
            })
            .catch(() => setError('We could not load the reading plans. Please try again.'))
            .finally(() => setIsLoading(false));
    }, []);

    const selected = useMemo(() => plans.find((plan) => plan.id === selectedId) ?? null, [plans, selectedId]);
    const completedIds = selected?.enrollment?.completed_day_ids ?? [];
    const percent = selected ? Math.round((completedIds.length / selected.duration_days) * 100) : 0;

    const updateEnrollment = (enrollment: UserReadingPlan) => {
        setPlans((current) => current.map((plan) => plan.id === enrollment.plan.id ? {
            ...plan,
            enrollment: {
                id: enrollment.id,
                started_at: enrollment.started_at,
                completed_at: enrollment.completed_at,
                completed_day_ids: enrollment.completed_day_ids,
            },
        } : plan));
    };

    const start = async () => {
        if (!selected) return;
        setIsStarting(true);
        setError('');
        try {
            const response = await apiRequest<ResourceResponse<UserReadingPlan>>(`/api/v1/reading-plans/${selected.id}/enrollments`, { method: 'POST' });
            updateEnrollment(response.data);
        } catch {
            setError('We could not start this plan. Please try again.');
        } finally {
            setIsStarting(false);
        }
    };

    const toggleDay = async (dayId: number, completed: boolean) => {
        if (!selected?.enrollment) return;
        setBusyDayId(dayId);
        setError('');
        try {
            const response = await apiRequest<ResourceResponse<UserReadingPlan>>(`/api/v1/reading-plan-enrollments/${selected.enrollment.id}/days/${dayId}`, {
                method: 'PUT',
                body: JSON.stringify({ completed }),
            });
            updateEnrollment(response.data);
        } catch (requestError) {
            setError(requestError instanceof ApiError ? requestError.message : 'Progress could not be updated. Please try again.');
        } finally {
            setBusyDayId(null);
        }
    };

    return (
        <AppShell>
            <header className="border-b border-black/10 pb-8"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Bible reading plans</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Build a steady rhythm.</h1><p className="mt-3 max-w-2xl text-stone-600">Choose a plan, read the assigned WEB passage, and return whenever you are ready for the next day.</p></header>
            {error && <div role="alert" className="mt-6 rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">{error}</div>}

            {isLoading ? <div className="mt-8 grid min-h-64 place-items-center rounded-[1.75rem] border border-dashed border-black/15 text-sm text-stone-500">Loading reading plans…</div> : plans.length === 0 ? (
                <div className="mt-8 rounded-[1.75rem] border border-dashed border-black/15 p-8 text-center"><h2 className="text-xl font-semibold">No plans are available yet.</h2><p className="mt-2 text-sm text-stone-500">Starter plans will appear after the reading-plan catalog is seeded.</p></div>
            ) : (
                <div className="mt-8 grid gap-8 xl:grid-cols-[20rem_minmax(0,1fr)]">
                    <aside className="grid content-start gap-3" aria-label="Available reading plans">
                        {plans.map((plan) => {
                            const complete = plan.enrollment?.completed_at !== null && plan.enrollment !== null;
                            const count = plan.enrollment?.completed_day_ids.length ?? 0;
                            return <button key={plan.id} onClick={() => { setSelectedId(plan.id); setOpenDayId(null); }} className={`rounded-2xl border p-5 text-left transition ${selectedId === plan.id ? 'border-ink bg-ink text-white' : 'border-black/10 bg-paper hover:border-moss/40'}`}>
                                <span className={`text-xs font-bold uppercase tracking-[0.16em] ${selectedId === plan.id ? 'text-acid' : 'text-moss'}`}>{plan.duration_days} days</span>
                                <span className="mt-2 block text-lg font-semibold">{plan.title}</span>
                                <span className={`mt-2 block text-xs ${selectedId === plan.id ? 'text-stone-400' : 'text-stone-500'}`}>{complete ? 'Completed' : plan.enrollment ? `${count} of ${plan.duration_days} complete` : 'Not started'}</span>
                            </button>;
                        })}
                    </aside>

                    {selected && <section>
                        <div className="rounded-[1.75rem] bg-ink p-6 text-white sm:p-8">
                            <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.2em] text-acid">{selected.duration_days}-day plan</p><h2 className="mt-2 text-3xl font-semibold tracking-[-0.04em]">{selected.title}</h2><p className="mt-3 max-w-2xl text-sm leading-6 text-stone-300">{selected.description}</p></div>{!selected.enrollment && <button onClick={start} disabled={isStarting} className="shrink-0 rounded-xl bg-acid px-5 py-3 text-sm font-bold text-ink disabled:opacity-60">{isStarting ? 'Starting…' : 'Start this plan'}</button>}</div>
                            {selected.enrollment && <div className="mt-6"><div className="flex justify-between text-xs"><span>{selected.enrollment.completed_at ? 'Plan complete' : 'Your progress'}</span><span>{completedIds.length}/{selected.duration_days} days</span></div><div className="mt-2 h-2 overflow-hidden rounded-full bg-white/10"><div className="h-full rounded-full bg-acid transition-all" style={{ width: `${percent}%` }} /></div></div>}
                        </div>

                        <div className="mt-5 grid gap-3">
                            {selected.days.map((day) => {
                                const completed = completedIds.includes(day.id);
                                const open = openDayId === day.id;
                                return <article key={day.id} className={`rounded-2xl border bg-paper ${completed ? 'border-moss/30' : 'border-black/10'}`}>
                                    <div className="flex items-center gap-3 p-4 sm:p-5">
                                        <button type="button" disabled={!selected.enrollment || busyDayId === day.id} onClick={() => toggleDay(day.id, !completed)} aria-label={`${completed ? 'Mark incomplete' : 'Mark complete'}: day ${day.day_number}`} className={`grid size-10 shrink-0 place-items-center rounded-full border text-sm font-bold ${completed ? 'border-moss bg-moss text-white' : 'border-stone-300 text-stone-500'} disabled:opacity-40`}>{busyDayId === day.id ? '…' : completed ? '✓' : day.day_number}</button>
                                        <button type="button" onClick={() => setOpenDayId(open ? null : day.id)} aria-expanded={open} className="flex min-w-0 flex-1 items-center justify-between gap-3 text-left"><span><span className="block font-semibold">{day.title}</span><span className="mt-1 block text-sm text-stone-500">{day.scripture_reference} · {day.translation}</span></span><span className="text-xl text-stone-400" aria-hidden="true">{open ? '−' : '+'}</span></button>
                                    </div>
                                    {open && <div className="border-t border-black/10 px-5 py-6 sm:px-16"><p className="text-xs font-bold uppercase tracking-[0.18em] text-moss">Exact Scripture · {day.translation}</p><p className="mt-3 whitespace-pre-line font-serif leading-8 text-stone-800">{day.scripture_text}</p>{!selected.enrollment && <p className="mt-5 rounded-xl bg-acid/30 p-4 text-sm text-moss">Start this plan to mark each day complete.</p>}</div>}
                                </article>;
                            })}
                        </div>
                    </section>}
                </div>
            )}
            <p className="mt-8 text-xs text-stone-500">Scripture text: World English Bible (WEB), 2020 stable text edition, Public Domain.</p>
        </AppShell>
    );
}
