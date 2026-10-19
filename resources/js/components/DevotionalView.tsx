import type { Devotional } from '../types';

export function DevotionalView({ devotional, compact = false }: { devotional: Devotional; compact?: boolean }) {
    return (
        <article className={`rounded-[1.75rem] border border-black/10 bg-paper ${compact ? 'p-5 sm:p-6' : 'p-6 sm:p-8'}`}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">{devotional.scripture_reference} · {devotional.translation}</p>
                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${devotional.is_ai_generated ? 'bg-ink text-acid' : 'bg-moss/10 text-moss'}`}>
                    {devotional.is_ai_generated ? 'AI-generated reflection' : 'Luma daily reflection'}
                </span>
            </div>
            <h2 className={`${compact ? 'mt-3 text-2xl' : 'mt-4 text-3xl sm:text-4xl'} font-semibold tracking-[-0.045em]`}>{devotional.title}</h2>
            <section className="mt-5 rounded-2xl bg-white/70 p-5 ring-1 ring-black/5" aria-label="Scripture quotation">
                <div className="flex items-center justify-between gap-3">
                    <h3 className="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Scripture · exact {devotional.translation} text</h3>
                </div>
                <p className={`mt-3 whitespace-pre-line font-serif leading-7 text-stone-800 ${compact ? 'line-clamp-5 text-sm' : 'text-base'}`}>{devotional.scripture_text}</p>
            </section>
            <section className="mt-6" aria-label="Reflection">
                <h3 className="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Reflection</h3>
                <p className="mt-2 leading-7 text-stone-700">{devotional.reflection}</p>
            </section>
            {!compact && (
                <div className="mt-6 grid gap-4 md:grid-cols-2">
                    <section className="rounded-2xl bg-acid/35 p-5"><h3 className="text-xs font-bold uppercase tracking-[0.18em] text-moss">Put it into practice</h3><p className="mt-2 leading-7 text-stone-800">{devotional.application}</p></section>
                    <section className="rounded-2xl bg-ink p-5 text-white"><h3 className="text-xs font-bold uppercase tracking-[0.18em] text-acid">Closing prayer</h3><p className="mt-2 leading-7 text-stone-200">{devotional.prayer}</p></section>
                </div>
            )}
        </article>
    );
}
