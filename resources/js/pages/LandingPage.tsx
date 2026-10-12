import { Link } from 'react-router-dom';
import { Brand } from '../components/Brand';

const steps = [
    { number: '01', title: 'Capture', copy: 'Bring a sermon link, transcript, or authorized media into one focused study space.', status: 'Available' },
    { number: '02', title: 'Understand', copy: 'Surface structure, key ideas, Scripture references, and meaningful questions.', status: 'Planned' },
    { number: '03', title: 'Apply', copy: 'Turn listening into prayerful next steps you can revisit throughout the week.', status: 'Planned' },
    { number: '04', title: 'Equip', copy: 'Grow in biblical literacy and respond to difficult questions with clarity and grace.', status: 'Planned' },
];

const features = [
    { mark: '01', title: 'Sermon study', copy: 'Keep the message, Scripture references, notes, and questions together.', phase: 'Phase 2–3' },
    { mark: '02', title: 'Weekly formation', copy: 'Carry Sunday’s insight into a steady devotional rhythm.', phase: 'Phase 4' },
    { mark: '03', title: 'Thoughtful apologetics', copy: 'Examine theological claims with context, care, and source awareness.', phase: 'Phase 5' },
];

const faqs = [
    ['Is LumaWord available now?', 'Yes. Create an account to submit sermons, track background processing, and review structured sermon notes.'],
    ['Will LumaWord replace my Bible or church community?', 'No. LumaWord is being designed as a study companion that supports Scripture reading, prayer, and participation in a local church—not as a substitute for them.'],
    ['What sermon links are supported?', 'LumaWord safely saves public HTTP and HTTPS sermon links. Transcript access depends on the provider and authorization; when it is unavailable, paste a transcript or upload media you are authorized to process.'],
    ['How much will Premium cost?', 'Pricing has not been set. LumaWord will not publish a price or promise plan-specific limits until those decisions are approved.'],
    ['Will there be a mobile app?', 'A native iOS experience is planned for a later phase. The web application uses a versioned Laravel API so the future app can share the same backend.'],
];

export function LandingPage() {
    return (
        <div className="overflow-hidden bg-paper text-ink">
            <header className="sticky top-0 z-40 border-b border-black/10 bg-paper/90 backdrop-blur-xl">
                <div className="mx-auto flex h-18 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
                    <Brand />
                    <nav className="hidden items-center gap-7 text-sm font-medium text-stone-600 md:flex" aria-label="Main navigation">
                        <a href="#how-it-works" className="hover:text-ink">How it works</a>
                        <a href="#features" className="hover:text-ink">Features</a>
                        <a href="#plans" className="hover:text-ink">Plans</a>
                        <a href="#faq" className="hover:text-ink">FAQ</a>
                    </nav>
                    <div className="flex items-center gap-2 sm:gap-3">
                        <Link to="/login" className="rounded-full px-4 py-2 text-sm font-semibold hover:bg-stone-200">Log in</Link>
                        <Link to="/register" className="rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-moss sm:px-5">Sign up</Link>
                    </div>
                </div>
            </header>

            <main>
                <section className="relative border-b border-black/10">
                    <div className="absolute inset-x-0 top-0 -z-0 h-96 bg-[radial-gradient(circle_at_75%_25%,rgba(200,255,61,0.28),transparent_42%)]" />
                    <div className="relative mx-auto grid max-w-7xl items-center gap-14 px-5 py-20 sm:px-8 sm:py-28 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 lg:py-32">
                        <div>
                            <div className="mb-7 inline-flex items-center gap-2 rounded-full border border-black/10 bg-white/60 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-stone-600">
                                <span className="size-2 rounded-full bg-lime-500" /> Sermon study is live
                            </div>
                            <h1 className="text-balance max-w-3xl text-5xl font-semibold leading-[0.98] tracking-[-0.065em] sm:text-6xl lg:text-7xl">
                                Don’t just hear the Word. <span className="relative whitespace-nowrap"><span className="relative z-10">Live it.</span><span className="absolute inset-x-0 bottom-1.5 -z-0 h-3 bg-acid sm:h-4" /></span>
                            </h1>
                            <p className="mt-7 max-w-xl text-lg leading-8 text-stone-600 sm:text-xl">
                                LumaWord is becoming a focused place to capture sermons, understand Scripture, build devotional rhythm, and grow in confident faith.
                            </p>
                            <div className="mt-9 flex flex-col gap-3 sm:flex-row">
                                <Link to="/register" className="inline-flex h-13 items-center justify-center rounded-full bg-ink px-7 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-moss">Create your account <span className="ml-3">→</span></Link>
                                <a href="#how-it-works" className="inline-flex h-13 items-center justify-center rounded-full border border-stone-300 bg-white/60 px-7 font-semibold transition hover:border-ink">Explore the vision</a>
                            </div>
                            <p className="mt-5 text-xs text-stone-500">No pricing commitment required. Future tools are labeled by phase.</p>
                        </div>

                        <div className="relative mx-auto w-full max-w-xl">
                            <div className="absolute -inset-8 rounded-full bg-acid/20 blur-3xl" />
                            <div className="relative overflow-hidden rounded-[2rem] border border-white/10 bg-ink p-3 shadow-2xl shadow-black/20">
                                <div className="flex items-center justify-between border-b border-white/10 px-4 py-3 text-xs text-stone-400">
                                    <span className="flex items-center gap-2"><span className="size-2 rounded-full bg-acid" /> Sermon workspace preview</span>
                                    <span>Now available</span>
                                </div>
                                <div className="grid gap-3 p-3 sm:grid-cols-[0.9fr_1.1fr]">
                                    <div className="rounded-2xl bg-[#1b1e18] p-5">
                                        <p className="text-xs uppercase tracking-[0.2em] text-stone-500">This week</p>
                                        <h2 className="mt-8 text-2xl font-medium tracking-[-0.04em] text-white">From listening to lasting formation.</h2>
                                        <div className="mt-10 h-1.5 overflow-hidden rounded-full bg-white/10"><div className="h-full w-2/3 rounded-full bg-acid" /></div>
                                        <p className="mt-3 text-xs text-stone-500">Illustrative preview — no sermon has been processed.</p>
                                    </div>
                                    <div className="rounded-2xl bg-[#f0eee5] p-5">
                                        <div className="flex items-center justify-between"><span className="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Study outline</span><span className="rounded-full bg-lime-200 px-2 py-1 text-[10px] font-bold text-moss">PREVIEW</span></div>
                                        <div className="mt-7 grid gap-3">
                                            {['Big idea', 'Scripture in context', 'Reflect & respond'].map((item, index) => (
                                                <div key={item} className="flex items-center gap-3 rounded-xl border border-black/8 bg-white/70 p-3">
                                                    <span className="grid size-7 place-items-center rounded-full bg-ink text-[10px] font-bold text-acid">{index + 1}</span>
                                                    <span className="text-sm font-medium">{item}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="how-it-works" className="bg-ink py-24 text-white sm:py-28">
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                        <div className="grid gap-6 lg:grid-cols-2 lg:items-end">
                            <div><p className="text-xs font-bold uppercase tracking-[0.24em] text-acid">A formation loop</p><h2 className="mt-4 text-balance text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Four movements. One deeper walk.</h2></div>
                            <p className="max-w-xl text-lg leading-8 text-stone-400 lg:justify-self-end">LumaWord is designed around what happens after the sermon ends: remembering, examining, practicing, and sharing what matters.</p>
                        </div>
                        <div className="mt-14 grid border-y border-white/10 sm:grid-cols-2 lg:grid-cols-4">
                            {steps.map((step) => (
                                <article key={step.title} className="border-white/10 py-8 sm:px-6 sm:odd:border-r lg:border-r lg:px-7 lg:first:pl-0 lg:last:border-r-0">
                                    <div className="flex items-center justify-between"><span className="font-mono text-xs text-acid">{step.number}</span><span className="text-[10px] uppercase tracking-widest text-stone-600">{step.status}</span></div>
                                    <h3 className="mt-14 text-2xl font-medium">{step.title}</h3>
                                    <p className="mt-4 text-sm leading-6 text-stone-400">{step.copy}</p>
                                </article>
                            ))}
                        </div>
                    </div>
                </section>

                <section id="features" className="py-24 sm:py-28">
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                        <p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">The LumaWord roadmap</p>
                        <h2 className="mt-4 max-w-3xl text-balance text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">One calm place for the questions that shape your faith.</h2>
                        <div className="mt-14 grid gap-5 lg:grid-cols-3">
                            {features.map((feature, index) => (
                                <article key={feature.title} className={`min-h-80 rounded-[1.75rem] border p-7 sm:p-8 ${index === 1 ? 'border-ink bg-acid' : 'border-black/10 bg-white/55'}`}>
                                    <div className="flex items-center justify-between"><span className="grid size-11 place-items-center rounded-full border border-black/15 font-mono text-xs">{feature.mark}</span><span className="rounded-full border border-black/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest">{feature.phase}</span></div>
                                    <h3 className="mt-20 text-2xl font-semibold tracking-[-0.035em]">{feature.title}</h3>
                                    <p className="mt-3 max-w-sm leading-7 text-stone-700">{feature.copy}</p>
                                </article>
                            ))}
                        </div>
                    </div>
                </section>

                <section id="plans" className="border-y border-black/10 bg-[#e9e6dc] py-24 sm:py-28">
                    <div className="mx-auto max-w-6xl px-5 sm:px-8">
                        <div className="text-center"><p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">Simple by design</p><h2 className="mt-4 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">A place to begin. Room to grow.</h2><p className="mx-auto mt-4 max-w-2xl text-stone-600">Plan contents and pricing will be finalized before subscriptions launch in Phase 6.</p></div>
                        <div className="mt-12 grid gap-5 md:grid-cols-2">
                            <article className="rounded-[2rem] border border-black/10 bg-paper p-8 sm:p-10">
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-stone-500">Free</span><h3 className="mt-4 text-3xl font-semibold tracking-[-0.04em]">Start with the essentials.</h3><p className="mt-4 leading-7 text-stone-600">A welcoming entry point for building a personal study rhythm. Final access limits are still an open decision.</p><p className="mt-10 border-t border-black/10 pt-5 text-sm font-semibold">Price to be announced</p>
                            </article>
                            <article className="relative overflow-hidden rounded-[2rem] bg-ink p-8 text-white sm:p-10">
                                <div className="absolute right-0 top-0 size-44 rounded-full bg-acid/15 blur-3xl" /><span className="relative text-xs font-bold uppercase tracking-[0.2em] text-acid">Premium</span><h3 className="relative mt-4 text-3xl font-semibold tracking-[-0.04em]">Go deeper over time.</h3><p className="relative mt-4 leading-7 text-stone-400">Planned for expanded study capacity and advanced tools. Exact benefits and limits require product approval.</p><p className="relative mt-10 border-t border-white/10 pt-5 text-sm font-semibold">Price to be announced</p>
                            </article>
                        </div>
                    </div>
                </section>

                <section id="faq" className="py-24 sm:py-28">
                    <div className="mx-auto grid max-w-6xl gap-12 px-5 sm:px-8 lg:grid-cols-[0.75fr_1.25fr]">
                        <div><p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">Questions, answered</p><h2 className="mt-4 text-4xl font-semibold tracking-[-0.055em]">Clear from the start.</h2></div>
                        <div className="divide-y divide-black/10 border-y border-black/10">
                            {faqs.map(([question, answer]) => (
                                <details key={question} className="group py-5">
                                    <summary className="flex cursor-pointer list-none items-center justify-between gap-5 font-semibold"><span>{question}</span><span className="text-xl font-light transition group-open:rotate-45">+</span></summary>
                                    <p className="max-w-2xl pt-4 text-sm leading-7 text-stone-600">{answer}</p>
                                </details>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="px-5 pb-5 sm:px-8 sm:pb-8">
                    <div className="mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-acid px-6 py-16 text-center sm:px-12 sm:py-20">
                        <p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">Begin with the foundation</p><h2 className="mx-auto mt-4 max-w-3xl text-balance text-4xl font-semibold tracking-[-0.06em] sm:text-6xl">Make room for the Word to stay with you.</h2><Link to="/register" className="mt-8 inline-flex h-13 items-center rounded-full bg-ink px-7 font-semibold text-white">Create your account <span className="ml-3">→</span></Link>
                    </div>
                </section>
            </main>

            <footer className="bg-ink px-5 py-12 text-white sm:px-8">
                <div className="mx-auto flex max-w-7xl flex-col gap-8 sm:flex-row sm:items-end sm:justify-between">
                    <div><Brand inverse /><p className="mt-4 max-w-sm text-sm leading-6 text-stone-500">AI-powered Christian study tools, built carefully and released in clear phases.</p></div>
                    <div className="flex flex-wrap gap-5 text-sm text-stone-400"><a href="#how-it-works" className="hover:text-white">How it works</a><a href="#faq" className="hover:text-white">FAQ</a><Link to="/login" className="hover:text-white">Log in</Link></div>
                </div>
                <div className="mx-auto mt-10 max-w-7xl border-t border-white/10 pt-5 text-xs text-stone-600">© {new Date().getFullYear()} LumaWord. Built for thoughtful discipleship.</div>
            </footer>
        </div>
    );
}
