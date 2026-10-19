import { useEffect, useState, type FormEvent } from 'react';
import { AppShell } from '../components/AppShell';
import { DevotionalView } from '../components/DevotionalView';
import { ApiError, apiRequest } from '../lib/api';
import type { Devotional, PaginatedResponse, ResourceResponse, ScriptureOption, Sermon } from '../types';

type ScriptureResponse = {
    data: ScriptureOption[];
    source: { name: string; edition: string; license: string; url: string };
};

export function DevotionalsPage() {
    const [today, setToday] = useState<Devotional | null>(null);
    const [saved, setSaved] = useState<Devotional[]>([]);
    const [scriptures, setScriptures] = useState<ScriptureOption[]>([]);
    const [sermons, setSermons] = useState<Sermon[]>([]);
    const [source, setSource] = useState<ScriptureResponse['source'] | null>(null);
    const [selected, setSelected] = useState<Devotional | null>(null);
    const [scriptureKey, setScriptureKey] = useState('psalm-23');
    const [sermonId, setSermonId] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isGenerating, setIsGenerating] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        Promise.all([
            apiRequest<ResourceResponse<Devotional>>('/api/v1/devotionals/today'),
            apiRequest<PaginatedResponse<Devotional>>('/api/v1/devotionals'),
            apiRequest<ScriptureResponse>('/api/v1/scriptures'),
            apiRequest<PaginatedResponse<Sermon>>('/api/v1/sermons?status=completed&per_page=50'),
        ]).then(([daily, history, scriptureResponse, sermonResponse]) => {
            setToday(daily.data);
            setSaved(history.data);
            setScriptures(scriptureResponse.data);
            setSource(scriptureResponse.source);
            setSermons(sermonResponse.data);
            setSelected(daily.data);
        }).catch(() => setError('We could not load your devotionals. Please try again.'))
            .finally(() => setIsLoading(false));
    }, []);

    const generate = async (event: FormEvent) => {
        event.preventDefault();
        setIsGenerating(true);
        setError('');

        try {
            const response = await apiRequest<ResourceResponse<Devotional>>('/api/v1/devotionals', {
                method: 'POST',
                body: JSON.stringify({
                    scripture_key: scriptureKey,
                    sermon_id: sermonId ? Number(sermonId) : null,
                }),
            });
            setSaved((current) => [response.data, ...current]);
            setSelected(response.data);
        } catch (requestError) {
            setError(requestError instanceof ApiError && requestError.status === 429
                ? 'You have reached today’s devotional generation limit. Your saved devotionals are still available.'
                : 'Luma could not generate this devotional. Nothing was saved; please try again.');
        } finally {
            setIsGenerating(false);
        }
    };

    return (
        <AppShell>
            <header className="border-b border-black/10 pb-8">
                <p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Daily devotionals</p>
                <h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Pause. Read. Respond.</h1>
                <p className="mt-3 max-w-2xl text-stone-600">Read today’s reflection or create one grounded in an exact public-domain Scripture passage and, optionally, one of your sermons.</p>
            </header>

            {error && <div role="alert" className="mt-6 rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">{error}</div>}

            {isLoading ? (
                <div className="mt-8 grid min-h-64 place-items-center rounded-[1.75rem] border border-dashed border-black/15 text-sm text-stone-500">Loading devotionals…</div>
            ) : (
                <div className="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div>{selected && <DevotionalView devotional={selected} />}</div>
                    <aside className="grid content-start gap-6">
                        <form onSubmit={generate} className="rounded-[1.75rem] bg-ink p-6 text-white">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-acid">Personalize</p>
                            <h2 className="mt-2 text-2xl font-semibold">Create a devotional</h2>
                            <label className="mt-5 block text-sm font-semibold" htmlFor="scripture">Bible passage</label>
                            <select id="scripture" value={scriptureKey} onChange={(event) => setScriptureKey(event.target.value)} className="mt-2 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-white">
                                {scriptures.map((option) => <option key={option.key} value={option.key} className="text-ink">{option.reference} ({option.translation})</option>)}
                            </select>
                            <label className="mt-4 block text-sm font-semibold" htmlFor="sermon">Sermon context <span className="font-normal text-stone-400">(optional)</span></label>
                            <select id="sermon" value={sermonId} onChange={(event) => setSermonId(event.target.value)} className="mt-2 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-white">
                                <option value="" className="text-ink">Passage only</option>
                                {sermons.map((sermon) => <option key={sermon.id} value={sermon.id} className="text-ink">{sermon.title}</option>)}
                            </select>
                            <button disabled={isGenerating} className="mt-5 w-full rounded-xl bg-acid px-4 py-3 text-sm font-bold text-ink transition hover:bg-white disabled:cursor-wait disabled:opacity-60">
                                {isGenerating ? 'Creating reflection…' : 'Generate and save'}
                            </button>
                            <p className="mt-3 text-xs leading-5 text-stone-400">AI writes only the reflection, application, and prayer. Scripture is copied unchanged from the WEB catalog.</p>
                        </form>

                        <section>
                            <div className="flex items-end justify-between gap-3"><h2 className="text-lg font-semibold">Saved devotionals</h2><span className="text-xs text-stone-500">{saved.length}</span></div>
                            <div className="mt-3 grid gap-2">
                                <button onClick={() => today && setSelected(today)} className="rounded-xl border border-black/10 bg-paper p-4 text-left text-sm transition hover:border-moss/40"><span className="font-semibold">Today’s devotional</span><span className="mt-1 block text-xs text-stone-500">Luma daily reflection</span></button>
                                {saved.length === 0 ? <p className="rounded-xl border border-dashed border-black/15 p-4 text-sm text-stone-500">Generated devotionals you save will appear here.</p> : saved.map((devotional) => (
                                    <button key={devotional.id} onClick={() => setSelected(devotional)} className="rounded-xl border border-black/10 bg-paper p-4 text-left text-sm transition hover:border-moss/40">
                                        <span className="font-semibold">{devotional.title}</span><span className="mt-1 block text-xs text-stone-500">{devotional.scripture_reference} · AI reflection</span>
                                    </button>
                                ))}
                            </div>
                        </section>
                    </aside>
                </div>
            )}

            {source && <p className="mt-8 text-xs leading-5 text-stone-500">Scripture: {source.name} ({source.edition}), {source.license}. <a className="font-semibold text-moss hover:underline" href={source.url} target="_blank" rel="noreferrer">Source and attribution</a>.</p>}
        </AppShell>
    );
}
