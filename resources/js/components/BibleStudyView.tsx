import type { BibleStudy } from '../types';

function TextList({ items }: { items: string[] }) {
    if (items.length === 0) return <p className="text-sm italic text-stone-500">No additional items were included at this study depth.</p>;

    return <ul className="grid gap-2 text-sm leading-6 text-stone-700">{items.map((item, index) => <li key={`${item}-${index}`} className="flex gap-3"><span aria-hidden="true" className="mt-2 size-1.5 shrink-0 rounded-full bg-moss" />{item}</li>)}</ul>;
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return <section className="rounded-[1.5rem] border border-black/10 bg-paper p-5 sm:p-7"><h2 className="text-2xl font-semibold tracking-[-0.035em]">{title}</h2><div className="mt-4">{children}</div></section>;
}

export function BibleStudyView({ study }: { study: BibleStudy }) {
    const content = study.content;

    return (
        <div className="grid gap-5">
            <section className="rounded-[1.75rem] bg-ink p-6 text-white sm:p-8">
                <div className="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-[0.16em]"><span className="rounded-full bg-acid px-3 py-1 text-ink">{study.depth_label}</span><span className="text-stone-400">{study.scripture_reference} · {study.translation}</span></div>
                <h1 className="mt-5 text-3xl font-semibold tracking-[-0.045em] sm:text-5xl">{study.title}</h1>
                <p className="mt-4 max-w-3xl text-base leading-7 text-stone-300">{content.overview}</p>
            </section>

            <Section title="The passage">
                <p className="whitespace-pre-line font-serif text-lg leading-8 text-stone-800">{study.scripture_text}</p>
                <p className="mt-5 border-t border-black/10 pt-5 text-sm leading-6 text-stone-600"><span className="font-semibold text-ink">Where it fits:</span> {content.passage_context}</p>
            </Section>

            <div className="grid gap-5 xl:grid-cols-2">
                <Section title="Book introduction">
                    <dl className="grid gap-4 text-sm leading-6 text-stone-700">
                        <div><dt className="font-semibold text-ink">Authorship</dt><dd>{content.book_introduction.authorship}</dd></div>
                        <div><dt className="font-semibold text-ink">Original audience</dt><dd>{content.book_introduction.audience}</dd></div>
                        <div><dt className="font-semibold text-ink">Purpose</dt><dd>{content.book_introduction.purpose}</dd></div>
                        <div><dt className="font-semibold text-ink">Larger biblical narrative</dt><dd>{content.book_introduction.biblical_narrative}</dd></div>
                    </dl>
                    {content.book_introduction.scholarly_uncertainties.length > 0 && <div className="mt-5 rounded-xl border border-amber-300 bg-amber-50 p-4"><h3 className="text-sm font-semibold text-amber-950">Scholarly questions</h3><div className="mt-2"><TextList items={content.book_introduction.scholarly_uncertainties} /></div></div>}
                </Section>
                <Section title="Historical and cultural context">
                    <div className="grid gap-3">{content.historical_context.length === 0 ? <p className="text-sm italic text-stone-500">No additional historical context was included.</p> : content.historical_context.map((item, index) => <article key={`${item.detail}-${index}`} className="rounded-xl border border-black/10 p-4"><span className={`inline-flex rounded-full px-2 py-1 text-[0.65rem] font-bold uppercase tracking-[0.12em] ${item.certainty === 'established' ? 'bg-moss/10 text-moss' : item.certainty === 'likely' ? 'bg-blue-100 text-blue-900' : 'bg-amber-100 text-amber-950'}`}>{item.certainty}</span><p className="mt-2 text-sm leading-6 text-stone-700">{item.detail}</p></article>)}</div>
                </Section>
            </div>

            {content.word_studies.length > 0 && <Section title="Original-language words"><div className="grid gap-4 md:grid-cols-2">{content.word_studies.map((word, index) => <article key={`${word.term}-${index}`} className="rounded-xl border border-black/10 p-4"><p className="text-xs font-bold uppercase tracking-[0.14em] text-moss">{word.language}</p><h3 className="mt-1 text-xl font-semibold">{word.term} <span className="font-normal text-stone-500">· {word.transliteration}</span></h3><p className="mt-1 text-sm text-stone-500">Pronounced {word.pronunciation}</p><p className="mt-3 text-sm leading-6 text-stone-700">{word.contextual_meaning}</p><p className="mt-3 text-sm"><span className="font-semibold">Possible translations:</span> {word.possible_translations.join(', ')}</p><p className="mt-3 text-sm leading-6 text-stone-700">{word.translation_significance}</p><p className="mt-3 rounded-lg bg-stone-100 p-3 text-xs leading-5 text-stone-600">{word.caution}</p></article>)}</div></Section>}

            <div className="grid gap-5 xl:grid-cols-2">
                <Section title="Book outline"><div className="grid gap-3">{content.book_outline.map((item, index) => <article key={`${item.heading}-${index}`} className={`rounded-xl border p-4 ${item.contains_passage ? 'border-moss bg-moss/5' : 'border-black/10'}`}><div className="flex flex-wrap items-baseline justify-between gap-2"><h3 className="font-semibold">{item.heading}</h3><span className="text-xs text-stone-500">{item.range}</span></div><p className="mt-2 text-sm leading-6 text-stone-700">{item.summary}</p>{item.contains_passage && <span className="mt-3 inline-flex rounded-full bg-acid px-2 py-1 text-xs font-semibold">Current passage</span>}</article>)}</div></Section>
                <Section title="Literary genre and structure"><p className="text-sm"><span className="font-semibold">Genre:</span> {content.literary_context.genre}</p><h3 className="mt-5 text-sm font-semibold">Structure</h3><div className="mt-2"><TextList items={content.literary_context.structure} /></div><h3 className="mt-5 text-sm font-semibold">Notable patterns and movements</h3><div className="mt-2"><TextList items={content.literary_context.observations} /></div></Section>
            </div>

            <Section title="Key themes"><div className="grid gap-3 md:grid-cols-2">{content.themes.map((item, index) => <article key={`${item.theme}-${index}`} className="rounded-xl bg-white/70 p-4"><h3 className="font-semibold">{item.theme}</h3><p className="mt-2 text-sm leading-6 text-stone-700">{item.explanation}</p></article>)}</div></Section>
            <Section title="Cross-references"><div className="grid gap-3">{content.cross_references.length === 0 ? <p className="text-sm italic text-stone-500">No confident cross-references were included.</p> : content.cross_references.map((item, index) => <article key={`${item.reference}-${index}`} className="grid gap-2 rounded-xl border border-black/10 p-4 sm:grid-cols-[9rem_1fr]"><h3 className="font-semibold text-moss">{item.reference}</h3><p className="text-sm leading-6 text-stone-700">{item.connection}</p></article>)}</div></Section>

            <div className="grid gap-5 xl:grid-cols-2">
                <Section title="Study questions"><TextList items={content.study_questions} /></Section>
                <Section title="Application and reflection"><h3 className="text-sm font-semibold">Practical reflections</h3><div className="mt-2"><TextList items={content.application.practical_reflections} /></div><h3 className="mt-5 text-sm font-semibold">Prayer prompts</h3><div className="mt-2"><TextList items={content.application.prayer_prompts} /></div><h3 className="mt-5 text-sm font-semibold">Journaling questions</h3><div className="mt-2"><TextList items={content.application.journaling_questions} /></div><h3 className="mt-5 text-sm font-semibold">Group discussion</h3><div className="mt-2"><TextList items={content.application.group_discussion_topics} /></div></Section>
            </div>
        </div>
    );
}
