import { Link } from 'react-router-dom';
import { Brand } from '../components/Brand';
import { useAuth } from '../context/AuthContext';

const features = [
    {
        mark: '01',
        title: 'Study a sermon',
        copy: 'Add a sermon link, transcript, or media you are allowed to use. Keep its main ideas, Bible references, notes, and questions together in one study space.',
        availability: 'In development',
    },
    {
        mark: '02',
        title: 'Build a weekly rhythm',
        copy: 'Turn a sermon or Bible passage into guided reading and reflection you can revisit during the week.',
        availability: 'Planned',
    },
    {
        mark: '03',
        title: 'Explore faith questions',
        copy: 'Examine a question or claim with Scripture context, supporting sources, and room for different Christian perspectives.',
        availability: 'Planned',
    },
    {
        mark: '04',
        title: 'Prepare a thoughtful response',
        copy: 'Organize what you have learned into a clear, respectful response for a conversation, lesson, or post.',
        availability: 'Planned',
    },
];

const steps = [
    {
        number: '01',
        title: 'Add a sermon',
        copy: 'Paste a link, add a transcript, or upload media you have permission to use. A sermon URL alone may not provide a transcript.',
        status: 'Available now',
    },
    {
        number: '02',
        title: 'Make a study space',
        copy: 'Keep sermon content, references, notes, and questions organized. AI notes require a transcript and configured processing.',
        status: 'In development',
    },
    {
        number: '03',
        title: 'Keep studying',
        copy: 'Future devotionals and reading plans will help you return to the message during the week.',
        status: 'Planned',
    },
    {
        number: '04',
        title: 'Explore and respond',
        copy: 'Future apologetics tools will support sourced explanations and thoughtful responses.',
        status: 'Planned',
    },
];

const audiences = [
    ['Believers', 'Keep Sunday’s message, Bible references, and personal notes in one place, then return to them during the week.'],
    ['Pastors and Bible teachers', 'Organize sermon study materials and explore questions that can support teaching and discussion.'],
    ['Churches and small groups', 'Give people a shared starting point for reflection, study, and thoughtful conversation. Group tools are planned.'],
    ['Christian creators', 'Research Scripture-based topics and prepare careful, sourced material. Creator workflows are planned.'],
];

const faqs = [
    ['What can I use in Luma today?', 'You can create a free account, save sermon links, paste authorized transcripts, upload authorized media, and keep sermon studies in your private library. AI processing depends on a configured queue worker and provider credentials.'],
    ['Does a sermon link automatically create a transcript?', 'No. Luma currently saves the public link, but it does not retrieve captions or download media from the provider. Paste a transcript or upload media you own or have permission to use.'],
    ['Can AI notes make mistakes?', 'Yes. AI-generated summaries and references can be incomplete or inaccurate. Check the sermon transcript, Scripture, and trusted sources before relying on them.'],
    ['Will Luma replace my Bible, pastor, or church community?', 'No. Luma is a study companion that supports Scripture reading, reflection, and thoughtful conversation—not a substitute for Scripture, pastors, teachers, or your own judgment.'],
    ['How much will Luma cost?', 'Plans are still taking shape. Plan details will be shared before subscriptions open.'],
];

export function LandingPage() {
    const { user, isLoading } = useAuth();

    return (
        <div className="overflow-x-hidden bg-paper text-ink">
            <header className="sticky top-0 z-40 border-b border-black/10 bg-paper/90 backdrop-blur-xl">
                <div className="mx-auto flex h-18 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
                    <Brand />
                    <nav className="hidden items-center gap-7 text-sm font-medium text-stone-600 lg:flex" aria-label="Main navigation">
                        <a href="#features" className="hover:text-ink">Features</a>
                        <a href="#how-it-works" className="hover:text-ink">How it works</a>
                        <a href="#audience" className="hover:text-ink">Who it’s for</a>
                        <a href="#plans" className="hover:text-ink">Plans</a>
                        <a href="#faq" className="hover:text-ink">FAQ</a>
                    </nav>
                    <div className="flex min-w-36 items-center justify-end gap-1 sm:min-w-40 sm:gap-3">
                        {isLoading ? (
                            <span className="h-10 w-28 animate-pulse rounded-full bg-stone-200 motion-reduce:animate-none" aria-label="Checking account" />
                        ) : user ? (
                            <>
                                <Link to="/settings" className="hidden rounded-full px-4 py-2 text-sm font-semibold hover:bg-stone-200 sm:inline-flex">Account</Link>
                                <Link to="/dashboard" className="rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-moss sm:px-5">Dashboard</Link>
                            </>
                        ) : (
                            <>
                                <Link to="/login" className="rounded-full px-3 py-2 text-sm font-semibold hover:bg-stone-200 sm:px-4">Sign in</Link>
                                <Link to="/register" className="rounded-full bg-ink px-3 py-2 text-sm font-semibold text-white transition hover:bg-moss sm:px-5">Create account</Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main>
                <section className="relative border-b border-black/10">
                    <div className="absolute inset-x-0 top-0 h-96 bg-[radial-gradient(circle_at_75%_25%,rgba(200,255,61,0.28),transparent_42%)]" />
                    <div className="relative mx-auto grid max-w-7xl items-center gap-14 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 lg:py-28">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">Scripture-centered sermon study</p>
                            <h1 className="mt-5 max-w-3xl text-balance text-[2.65rem] font-semibold leading-[1.02] tracking-[-0.06em] sm:text-6xl lg:text-7xl">Turn sermons into a study you can return to.</h1>
                            <p className="mt-7 max-w-xl text-lg leading-8 text-stone-700 sm:text-xl">Luma helps you keep a sermon’s main ideas, Bible references, notes, and questions together, so you can keep learning after Sunday.</p>
                            <p className="mt-4 max-w-xl text-sm font-semibold leading-6 text-moss">For believers, pastors, Bible teachers, churches, and Christian creators.</p>
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Link to="/register" className="inline-flex min-h-13 items-center justify-center rounded-full bg-ink px-7 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-moss motion-reduce:hover:translate-y-0">Create your free account <span className="ml-3" aria-hidden="true">→</span></Link>
                                <a href="#how-it-works" className="inline-flex min-h-13 items-center justify-center rounded-full border border-stone-300 bg-white/60 px-7 font-semibold transition hover:border-ink">See how sermon study works</a>
                            </div>
                            <p className="mt-5 max-w-xl text-xs leading-5 text-stone-600">Saving sermon links is available now. Transcript processing and AI notes are in development; devotional, apologetics, and group tools are planned.</p>
                        </div>

                        <div className="relative mx-auto w-full max-w-xl">
                            <div className="absolute -inset-8 rounded-full bg-acid/20 blur-3xl" />
                            <figure className="relative overflow-hidden rounded-[2rem] border border-white/10 bg-ink p-3 shadow-2xl shadow-black/20">
                                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-white/10 px-4 py-3 text-xs text-stone-400"><span className="flex items-center gap-2"><span className="size-2 rounded-full bg-acid" /> Example sermon study space</span><span>Sample content</span></div>
                                <div className="grid gap-3 p-3 sm:grid-cols-[0.9fr_1.1fr]">
                                    <div className="rounded-2xl bg-[#1b1e18] p-5"><p className="text-xs uppercase tracking-[0.2em] text-stone-400">Sample content</p><h2 className="mt-8 text-2xl font-medium tracking-[-0.04em] text-white">From listening to lasting formation.</h2><div className="mt-10 h-1.5 overflow-hidden rounded-full bg-white/10"><div className="h-full w-2/3 rounded-full bg-acid" /></div><p className="mt-3 text-xs leading-5 text-stone-400">Illustrative preview—not a processed sermon.</p></div>
                                    <div className="rounded-2xl bg-[#f0eee5] p-5"><div className="flex items-center justify-between gap-3"><span className="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Study outline</span><span className="rounded-full bg-lime-200 px-2 py-1 text-[10px] font-bold text-moss">Sample</span></div><div className="mt-7 grid gap-3">{['Big idea', 'Scripture in context', 'Reflect and respond'].map((item, index) => <div key={item} className="flex items-center gap-3 rounded-xl border border-black/8 bg-white/70 p-3"><span className="grid size-7 place-items-center rounded-full bg-ink text-[10px] font-bold text-acid">{index + 1}</span><span className="text-sm font-medium">{item}</span></div>)}</div></div>
                                </div>
                                <figcaption className="sr-only">An illustrative example of a Luma sermon study space using sample content.</figcaption>
                            </figure>
                        </div>
                    </div>
                </section>

                <section id="features" className="py-24 sm:py-28">
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                        <p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">Start with sermon study</p><h2 className="mt-4 max-w-3xl text-balance text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">What you can do with Luma</h2><p className="mt-5 max-w-2xl text-lg leading-8 text-stone-600">Start with sermon study. More ways to build understanding and a steady faith practice are on the way.</p>
                        <div className="mt-12 grid gap-5 md:grid-cols-2">{features.map((feature, index) => <article key={feature.title} className={`rounded-[1.75rem] border p-7 sm:p-8 ${index === 0 ? 'border-ink bg-acid' : 'border-black/10 bg-white/55'}`}><div className="flex flex-wrap items-center justify-between gap-3"><span className="grid size-11 place-items-center rounded-full border border-black/15 font-mono text-xs">{feature.mark}</span><span className="rounded-full border border-black/15 px-3 py-1 text-[10px] font-bold uppercase tracking-widest">{feature.availability}</span></div><h3 className="mt-12 text-2xl font-semibold tracking-[-0.035em]">{feature.title}</h3><p className="mt-3 max-w-xl leading-7 text-stone-700">{feature.copy}</p></article>)}</div>
                    </div>
                </section>

                <section id="how-it-works" className="bg-ink py-24 text-white sm:py-28">
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                        <div className="grid gap-6 lg:grid-cols-2 lg:items-end"><div><p className="text-xs font-bold uppercase tracking-[0.24em] text-acid">How it works</p><h2 className="mt-4 text-balance text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">From a sermon to a study space.</h2></div><p className="max-w-xl text-lg leading-8 text-stone-300 lg:justify-self-end">Luma supports study after the sermon ends. It does not replace Scripture, pastors, teachers, or your own judgment.</p></div>
                        <div className="mt-14 grid border-y border-white/10 sm:grid-cols-2 lg:grid-cols-4">{steps.map((step) => <article key={step.title} className="border-white/10 py-8 sm:px-6 sm:odd:border-r lg:border-r lg:px-7 lg:first:pl-0 lg:last:border-r-0"><div className="flex items-start justify-between gap-3"><span className="font-mono text-xs text-acid">{step.number}</span><span className="text-right text-[10px] font-semibold uppercase tracking-widest text-stone-300">{step.status}</span></div><h3 className="mt-10 text-2xl font-medium">{step.title}</h3><p className="mt-4 text-sm leading-6 text-stone-300">{step.copy}</p></article>)}</div>
                    </div>
                </section>

                <section id="audience" className="py-24 sm:py-28">
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10"><p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">Who Luma is for</p><h2 className="mt-4 max-w-4xl text-balance text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Made for people who want to keep growing in faith.</h2><div className="mt-12 grid gap-px overflow-hidden rounded-[1.75rem] border border-black/10 bg-black/10 md:grid-cols-2">{audiences.map(([title, copy]) => <article key={title} className="bg-paper p-7 sm:p-8"><h3 className="text-xl font-semibold">{title}</h3><p className="mt-3 leading-7 text-stone-600">{copy}</p></article>)}</div></div>
                </section>

                <section className="border-y border-black/10 bg-white/50 py-16 sm:py-20"><div className="mx-auto grid max-w-6xl gap-8 px-5 sm:px-8 lg:grid-cols-3"><article><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">Use content responsibly</p><p className="mt-3 text-sm leading-7 text-stone-700">Only add media or transcripts you own or have permission to use. Luma does not bypass provider controls or scrape protected content.</p></article><article><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">Check what AI creates</p><p className="mt-3 text-sm leading-7 text-stone-700">Generated notes may miss context or make mistakes. Compare them with the transcript, Scripture, and trusted sources.</p></article><article><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">Study support, not authority</p><p className="mt-3 text-sm leading-7 text-stone-700">Luma helps organize study. It does not settle every theological question or replace Christian community and leadership.</p></article></div></section>

                <section id="plans" className="bg-[#e9e6dc] py-24 sm:py-28"><div className="mx-auto max-w-4xl px-5 text-center sm:px-8"><p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">Plans</p><h2 className="mt-4 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Plans are taking shape.</h2><p className="mx-auto mt-5 max-w-2xl text-lg leading-8 text-stone-700">Sermon Study is the first tool in development; devotional, apologetics, and group features are planned. We will share plan details before subscriptions open.</p></div></section>

                <section id="faq" className="py-24 sm:py-28"><div className="mx-auto grid max-w-6xl gap-12 px-5 sm:px-8 lg:grid-cols-[0.75fr_1.25fr]"><div><p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">Questions, answered</p><h2 className="mt-4 text-4xl font-semibold tracking-[-0.055em]">Clear from the start.</h2></div><div className="divide-y divide-black/10 border-y border-black/10">{faqs.map(([question, answer]) => <details key={question} className="group py-5"><summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-5 font-semibold"><span>{question}</span><span className="text-xl font-light transition group-open:rotate-45 motion-reduce:transition-none" aria-hidden="true">+</span></summary><p className="max-w-2xl pt-4 text-sm leading-7 text-stone-600">{answer}</p></details>)}</div></div></section>

                <section className="px-5 pb-5 sm:px-8 sm:pb-8"><div className="mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-acid px-6 py-16 text-center sm:px-12 sm:py-20"><p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">Understand the Word. Live it through the week.</p><h2 className="mx-auto mt-4 max-w-3xl text-balance text-4xl font-semibold tracking-[-0.06em] sm:text-6xl">Make room for the Word to stay with you.</h2><Link to="/register" className="mt-8 inline-flex min-h-13 items-center rounded-full bg-ink px-7 font-semibold text-white">Create your free account <span className="ml-3" aria-hidden="true">→</span></Link></div></section>
            </main>

            <footer className="bg-ink px-5 py-12 text-white sm:px-8"><div className="mx-auto flex max-w-7xl flex-col gap-8 sm:flex-row sm:items-end sm:justify-between"><div><Brand inverse /><p className="mt-4 max-w-md text-sm leading-6 text-stone-400">Luma is a Scripture-centered study space for believers who want to keep learning from sermons and grow in faith.</p></div><div className="flex flex-wrap gap-5 text-sm text-stone-300"><a href="#features" className="hover:text-white">Features</a><a href="#how-it-works" className="hover:text-white">How it works</a><a href="#faq" className="hover:text-white">FAQ</a><Link to="/login" className="hover:text-white">Sign in</Link></div></div><div className="mx-auto mt-10 max-w-7xl border-t border-white/10 pt-5 text-xs text-stone-400">© {new Date().getFullYear()} Luma. Built for thoughtful discipleship.</div></footer>
        </div>
    );
}
