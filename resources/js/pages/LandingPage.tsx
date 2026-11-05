import { Link } from 'react-router-dom';
import { Brand } from '../components/Brand';
import { useAuth } from '../context/AuthContext';

const features = [
    ['01', 'Daily devotionals', 'Begin with Scripture, reflection, prayer, and a simple next step for the day.'],
    ['02', 'Sermon to study', 'Keep authorized sermon content, Bible references, notes, and questions together in one study space.'],
    ['03', 'Explore biblical context', 'Study passages with historical context, themes, word studies, cross-references, and reflection prompts.'],
    ['04', 'Build a reading rhythm', 'Follow guided reading plans and mark your progress as you spend time in Scripture.'],
];

const joinSteps = [
    ['1', 'Visit the LUMA login page', 'Sign in if you already have an account, or choose Create account.'],
    ['2', 'Create a free account', 'Start with the Free plan—no paid membership is required to join the beta.'],
    ['3', 'Explore and share feedback', 'Use the live beta features, then tell us what helped and what could be clearer.'],
];

export function LandingPage() {
    const { user, isLoading } = useAuth();

    return <div className="overflow-x-hidden bg-paper text-ink">
        <header className="sticky top-0 z-40 border-b border-black/10 bg-paper/90 backdrop-blur-xl">
            <div className="mx-auto flex min-h-18 max-w-7xl items-center justify-between gap-4 px-5 py-3 sm:px-8 lg:px-10">
                <Brand />
                <nav className="hidden items-center gap-7 text-sm font-medium text-stone-600 lg:flex" aria-label="Main navigation">
                    <a href="#what-is-luma" className="hover:text-ink">What is LUMA?</a><a href="#features" className="hover:text-ink">Explore</a><a href="#join" className="hover:text-ink">Join the beta</a><a href="#feedback" className="hover:text-ink">Feedback</a>
                </nav>
                <div className="flex items-center justify-end gap-2">
                    {isLoading ? <span className="h-10 w-28 animate-pulse rounded-full bg-stone-200 motion-reduce:animate-none" aria-label="Checking account" /> : user ? <Link to="/dashboard" className="rounded-full bg-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-moss">Dashboard</Link> : <><Link to="/login" className="rounded-full px-3 py-2 text-sm font-semibold hover:bg-stone-200">Sign in</Link><Link to="/register" className="hidden rounded-full bg-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-moss sm:inline-flex">Create account</Link></>}
                </div>
            </div>
        </header>

        <main>
            <section className="relative border-b border-black/10">
                <div className="absolute -right-28 top-20 h-96 w-96 rotate-[-18deg] bg-acid/35 blur-3xl" />
                <div className="relative mx-auto grid max-w-7xl items-center gap-14 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 lg:py-28">
                    <div>
                        <p className="inline-flex rounded-full border border-moss/20 bg-acid/25 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-moss">The public beta is open</p>
                        <h1 className="mt-6 max-w-3xl text-balance text-[3.2rem] font-semibold leading-[0.95] tracking-[-0.07em] sm:text-7xl lg:text-[5.5rem]">LUMA Beta Is Here</h1>
                        <p className="mt-7 max-w-xl text-xl leading-8 text-stone-700">A Christian study app for devotionals, sermon-based study, and biblical context.</p>
                        <p className="mt-4 max-w-xl font-semibold leading-7">Create a free account. Help shape what comes next.</p>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row"><Link to="/login" className="inline-flex min-h-13 items-center justify-center rounded-full bg-ink px-7 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-moss motion-reduce:hover:translate-y-0">Join the Free Beta <span className="ml-3" aria-hidden="true">→</span></Link><a href="#features" className="inline-flex min-h-13 items-center justify-center rounded-full border border-stone-300 bg-white/60 px-7 font-semibold hover:border-ink">See what’s inside</a></div>
                        <p className="mt-5 text-xs leading-5 text-stone-600">Free beta access includes available Free-plan features. AI-generated material can make mistakes—check Scripture and trusted sources.</p>
                    </div>
                    <div className="relative mx-auto w-full max-w-xl" aria-label="Illustrative preview of the LUMA beta interface">
                        <div className="absolute -inset-5 rotate-3 rounded-[2.2rem] bg-acid" />
                        <div className="relative overflow-hidden rounded-[2rem] border border-white/10 bg-ink p-4 shadow-2xl shadow-black/25">
                            <div className="flex items-center justify-between border-b border-white/10 px-3 pb-4 text-xs text-stone-400"><span>Interface preview</span><span className="rounded-full bg-acid px-2 py-1 font-bold text-ink">BETA</span></div>
                            <div className="grid gap-3 pt-4 sm:grid-cols-[0.8fr_1.2fr]"><div className="grid gap-3"><div className="rounded-2xl bg-paper p-4 text-ink"><p className="text-xs font-bold uppercase tracking-wider text-moss">Today</p><h2 className="mt-2 text-lg font-semibold">Daily devotional</h2><p className="mt-2 text-xs leading-5 text-stone-600">Read. Reflect. Pray. Apply.</p></div><div className="rounded-2xl border border-white/10 bg-white/5 p-4 text-white"><p className="text-xs text-stone-400">Reading plan</p><strong className="mt-2 block">Keep growing</strong><div className="mt-4 h-2 rounded-full bg-white/10"><div className="h-full w-2/3 rounded-full bg-acid" /></div></div></div><div className="rounded-2xl bg-white p-5 text-ink"><p className="text-xs font-bold uppercase tracking-wider text-moss">Sermon study</p><h2 className="mt-2 text-xl font-semibold">Take the message further.</h2><div className="mt-5 grid gap-2">{['Key ideas and Scripture', 'Personal notes', 'Ask LUMA'].map((label) => <div key={label} className="flex items-center gap-3 rounded-xl bg-stone-100 p-3 text-sm"><span className="grid size-7 place-items-center rounded-lg bg-acid font-bold">✓</span>{label}</div>)}</div></div></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="what-is-luma" className="mx-auto grid max-w-7xl gap-8 px-5 py-20 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:px-10 lg:py-28"><div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">What is LUMA?</p><h2 className="mt-4 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">A place to keep growing in the Word.</h2></div><div className="grid gap-5 text-lg leading-8 text-stone-700"><p>LUMA brings everyday Scripture study, sermon reflection, and biblical context into one focused experience.</p><p>Use it as a companion to Scripture and Christian community—not as a replacement for your Bible, pastor, church, or personal judgment.</p></div></section>

            <section id="features" className="bg-ink py-20 text-white lg:py-28"><div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10"><p className="text-xs font-bold uppercase tracking-[0.22em] text-acid">Available in the beta</p><div className="mt-8 grid gap-px overflow-hidden rounded-[2rem] bg-white/10 sm:grid-cols-2">{features.map(([number, title, copy]) => <article key={number} className="bg-ink p-7 sm:p-9"><span className="text-sm font-bold text-acid">{number}</span><h2 className="mt-8 text-2xl font-semibold">{title}</h2><p className="mt-3 max-w-md text-sm leading-6 text-stone-400">{copy}</p></article>)}</div></div></section>

            <section id="join" className="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-28"><div className="max-w-2xl"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">How to join the beta</p><h2 className="mt-4 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Three simple steps.</h2></div><div className="mt-10 grid gap-4 lg:grid-cols-3">{joinSteps.map(([number, title, copy]) => <article key={number} className="rounded-[1.75rem] border border-black/10 bg-white/60 p-7"><span className="grid size-10 place-items-center rounded-full bg-acid font-bold">{number}</span><h3 className="mt-8 text-xl font-semibold">{title}</h3><p className="mt-3 text-sm leading-6 text-stone-600">{copy}</p></article>)}</div></section>

            <section id="feedback" className="border-y border-black/10 bg-acid/35"><div className="mx-auto grid max-w-7xl items-center gap-8 px-5 py-16 sm:px-8 lg:grid-cols-[1fr_auto] lg:px-10 lg:py-20"><div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Help shape LUMA</p><h2 className="mt-4 text-4xl font-semibold tracking-[-0.055em]">What felt useful? What was confusing?</h2><p className="mt-4 max-w-2xl leading-7 text-stone-700">Beta feedback goes directly to the LUMA team. Sign in, explore the app, and tell us what would make you come back.</p></div><Link to={user ? '/feedback' : '/login'} className="inline-flex min-h-13 items-center justify-center rounded-full bg-ink px-7 font-semibold text-white hover:bg-moss">{user ? 'Share feedback' : 'Join and share feedback'} <span className="ml-3" aria-hidden="true">→</span></Link></div></section>
        </main>
        <footer className="bg-ink text-stone-400"><div className="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10"><Brand inverse /><p className="text-xs">LUMA is a study companion. Verify AI-generated material with Scripture and trusted sources.</p></div></footer>
    </div>;
}
