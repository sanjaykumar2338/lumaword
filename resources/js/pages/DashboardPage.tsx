import { AppShell } from '../components/AppShell';
import { useAuth } from '../context/AuthContext';

const upcoming = [
    { title: 'Sermon library', copy: 'Your saved sermons will live here once ingestion launches.', phase: 'Phase 2', symbol: '↗' },
    { title: 'AI notes', copy: 'Structured outlines, key themes, and references are planned next.', phase: 'Phase 2', symbol: '✦' },
    { title: 'Devotionals', copy: 'Weekly journeys and reading plans will build on your study.', phase: 'Phase 4', symbol: '◐' },
    { title: 'Apologetics', copy: 'Careful claim analysis and faith questions arrive later.', phase: 'Phase 5', symbol: '◇' },
];

export function DashboardPage() {
    const { user } = useAuth();
    const firstName = user?.name.split(' ')[0] ?? 'friend';

    return (
        <AppShell>
            <header className="flex flex-col gap-3 border-b border-black/10 pb-8 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Your LumaWord</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Welcome, {firstName}.</h1></div>
                <p className="max-w-md text-sm leading-6 text-stone-600 sm:text-right">The foundation is ready. Sermon study begins in the next phase.</p>
            </header>

            <section className="relative mt-8 overflow-hidden rounded-[2rem] bg-ink px-6 py-8 text-white sm:px-9 sm:py-10">
                <div className="absolute -right-12 -top-20 size-64 rounded-full bg-acid/20 blur-3xl" />
                <div className="relative grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
                    <div><span className="inline-flex rounded-full bg-acid px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-ink">Coming in Phase 2</span><h2 className="mt-5 text-3xl font-medium tracking-[-0.045em]">Bring a sermon into focus.</h2><p className="mt-3 max-w-md text-sm leading-6 text-stone-400">Soon, you’ll paste a supported sermon link or provide a transcript. Nothing is submitted or processed in this release.</p></div>
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-3 sm:flex sm:gap-3">
                        <label htmlFor="sermon-url" className="sr-only">Sermon link</label>
                        <input id="sermon-url" type="url" disabled placeholder="Paste a sermon link" className="h-12 w-full rounded-xl border border-white/10 bg-black/20 px-4 text-sm text-stone-300 placeholder:text-stone-600 disabled:cursor-not-allowed" />
                        <button disabled className="mt-3 h-12 w-full cursor-not-allowed rounded-xl bg-stone-700 px-5 text-sm font-semibold text-stone-400 sm:mt-0 sm:w-auto sm:whitespace-nowrap">Available in Phase 2</button>
                    </div>
                </div>
            </section>

            <section className="mt-10">
                <div className="flex items-end justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.22em] text-stone-500">Your study space</p><h2 className="mt-2 text-2xl font-semibold tracking-[-0.04em]">What’s ahead</h2></div><span className="hidden text-xs text-stone-500 sm:block">Roadmap placeholders — no generated content</span></div>
                <div className="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {upcoming.map((item) => (
                        <article key={item.title} className="flex min-h-56 flex-col rounded-2xl border border-black/10 bg-paper p-6 shadow-sm shadow-black/5">
                            <div className="flex items-center justify-between"><span className="grid size-10 place-items-center rounded-full bg-white text-lg" aria-hidden="true">{item.symbol}</span><span className="text-[10px] font-bold uppercase tracking-widest text-stone-500">{item.phase}</span></div>
                            <h3 className="mt-auto pt-10 text-lg font-semibold">{item.title}</h3><p className="mt-2 text-sm leading-6 text-stone-600">{item.copy}</p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="mt-8 grid gap-4 lg:grid-cols-[1.5fr_0.5fr]">
                <div className="rounded-2xl border border-black/10 bg-paper p-6 sm:p-8"><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">A simple next step</p><h2 className="mt-3 text-2xl font-semibold tracking-[-0.04em]">Prepare for your first study.</h2><p className="mt-3 max-w-2xl text-sm leading-6 text-stone-600">Keep the sermon link and any available transcript. Phase 2 will define supported sources, privacy controls, and ingestion limits before processing begins.</p></div>
                <div className="rounded-2xl bg-acid p-6 sm:p-8"><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">Foundation</p><p className="mt-8 text-4xl font-semibold tracking-[-0.06em]">01</p><p className="mt-1 text-sm text-moss">of 08 phases</p></div>
            </section>
        </AppShell>
    );
}
