import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { AppShell } from '../components/AppShell';
import { ApiError, apiRequest } from '../lib/api';
import type { BibleStudy, BibleStudyDepth, PaginatedResponse, ResourceResponse, ScriptureOption } from '../types';

type ScriptureResponse = {
    data: ScriptureOption[];
    source: { name: string; edition: string; license: string; url: string };
};

const depths: Array<{ value: BibleStudyDepth; title: string; description: string }> = [
    { value: 'quick_read', title: 'Quick Read', description: 'A short, plain-language overview with focused themes and questions.' },
    { value: 'go_deeper', title: 'Go Deeper', description: 'Expanded context, word meanings, literary observations, and connections.' },
    { value: 'study_in_depth', title: 'Study in Depth', description: 'Detailed interpretation, background, structure, uncertainty, and cross-references.' },
];

export function BibleStudiesPage() {
    const navigate = useNavigate();
    const [scriptures, setScriptures] = useState<ScriptureOption[]>([]);
    const [studies, setStudies] = useState<BibleStudy[]>([]);
    const [source, setSource] = useState<ScriptureResponse['source'] | null>(null);
    const [book, setBook] = useState('');
    const [chapter, setChapter] = useState<number | null>(null);
    const [scriptureKey, setScriptureKey] = useState('');
    const [depth, setDepth] = useState<BibleStudyDepth>('go_deeper');
    const [isLoading, setIsLoading] = useState(true);
    const [isGenerating, setIsGenerating] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        Promise.all([
            apiRequest<ScriptureResponse>('/api/v1/scriptures'),
            apiRequest<PaginatedResponse<BibleStudy>>('/api/v1/bible-studies'),
        ]).then(([scriptureResponse, studyResponse]) => {
            setScriptures(scriptureResponse.data);
            setSource(scriptureResponse.source);
            setStudies(studyResponse.data);
            const first = scriptureResponse.data[0];
            if (first) {
                setBook(first.book);
                setChapter(first.chapter);
                setScriptureKey(first.key);
            }
        }).catch(() => setError('We could not load the Bible study library. Please try again.'))
            .finally(() => setIsLoading(false));
    }, []);

    const books = useMemo(() => [...new Set(scriptures.map((option) => option.book))], [scriptures]);
    const chapters = useMemo(() => [...new Set(scriptures.filter((option) => option.book === book).map((option) => option.chapter))], [scriptures, book]);
    const passages = useMemo(() => scriptures.filter((option) => option.book === book && option.chapter === chapter), [scriptures, book, chapter]);

    const chooseBook = (nextBook: string) => {
        const first = scriptures.find((option) => option.book === nextBook);
        setBook(nextBook);
        setChapter(first?.chapter ?? null);
        setScriptureKey(first?.key ?? '');
    };

    const chooseChapter = (nextChapter: number) => {
        const first = scriptures.find((option) => option.book === book && option.chapter === nextChapter);
        setChapter(nextChapter);
        setScriptureKey(first?.key ?? '');
    };

    const generate = async (event: FormEvent) => {
        event.preventDefault();
        if (!scriptureKey) return;
        setIsGenerating(true);
        setError('');

        try {
            const response = await apiRequest<ResourceResponse<BibleStudy> & { meta: { reused: boolean } }>('/api/v1/bible-studies', {
                method: 'POST',
                body: JSON.stringify({ scripture_key: scriptureKey, depth }),
            });
            navigate(`/bible-studies/${response.data.id}`);
        } catch (requestError) {
            if (requestError instanceof ApiError && requestError.status === 429) {
                setError('You have reached today\'s study-generation limit. Your saved studies remain available.');
            } else if (requestError instanceof ApiError && requestError.status === 409) {
                setError(requestError.message);
            } else {
                setError(requestError instanceof ApiError ? requestError.message : 'Luma could not generate this study. Nothing was saved; please try again.');
            }
        } finally {
            setIsGenerating(false);
        }
    };

    return (
        <AppShell>
            <header className="border-b border-black/10 pb-8"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Bible study tools</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Study the passage in context.</h1><p className="mt-3 max-w-3xl text-stone-600">Choose an approved public-domain passage and a study depth. Luma keeps the Scripture text distinct from AI-generated commentary and saves the finished study privately to your library.</p></header>
            {error && <div role="alert" className="mt-6 rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">{error}</div>}

            {isLoading ? <div className="mt-8 grid min-h-64 place-items-center rounded-[1.75rem] border border-dashed border-black/15 text-sm text-stone-500">Loading study tools...</div> : (
                <div className="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <form onSubmit={generate} className="rounded-[1.75rem] bg-ink p-6 text-white sm:p-8">
                        <p className="text-xs font-bold uppercase tracking-[0.2em] text-acid">New study</p><h2 className="mt-2 text-3xl font-semibold tracking-[-0.04em]">Choose your passage</h2>
                        <div className="mt-6 grid gap-4 sm:grid-cols-3">
                            <label className="text-sm font-semibold">Book<select value={book} onChange={(event) => chooseBook(event.target.value)} className="mt-2 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-white">{books.map((name) => <option key={name} value={name} className="text-ink">{name}</option>)}</select></label>
                            <label className="text-sm font-semibold">Chapter<select value={chapter ?? ''} onChange={(event) => chooseChapter(Number(event.target.value))} className="mt-2 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-white">{chapters.map((number) => <option key={number} value={number} className="text-ink">{number}</option>)}</select></label>
                            <label className="text-sm font-semibold">Verse range<select value={scriptureKey} onChange={(event) => setScriptureKey(event.target.value)} className="mt-2 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-white">{passages.map((option) => <option key={option.key} value={option.key} className="text-ink">{option.verse_start}-{option.verse_end}</option>)}</select></label>
                        </div>

                        <fieldset className="mt-8"><legend className="text-sm font-semibold">Study depth</legend><div className="mt-3 grid gap-3 lg:grid-cols-3">{depths.map((option) => <label key={option.value} className={`cursor-pointer rounded-xl border p-4 transition ${depth === option.value ? 'border-acid bg-acid/10' : 'border-white/15 hover:border-white/40'}`}><input type="radio" name="depth" value={option.value} checked={depth === option.value} onChange={() => setDepth(option.value)} className="sr-only" /><span className={`block font-semibold ${depth === option.value ? 'text-acid' : 'text-white'}`}>{option.title}</span><span className="mt-2 block text-xs leading-5 text-stone-400">{option.description}</span></label>)}</div></fieldset>
                        <button disabled={isGenerating || !scriptureKey} className="mt-7 w-full rounded-xl bg-acid px-5 py-3.5 text-sm font-bold text-ink transition hover:bg-white disabled:cursor-wait disabled:opacity-60">{isGenerating ? 'Generating and saving...' : 'Generate and save study'}</button>
                        <p className="mt-3 text-xs leading-5 text-stone-400">Repeated requests for the same passage and depth reopen the saved study without another AI call.</p>
                    </form>

                    <aside><div className="flex items-end justify-between gap-3"><h2 className="text-xl font-semibold">Your study library</h2><span className="text-xs text-stone-500">{studies.length}</span></div><div className="mt-4 grid gap-3">{studies.length === 0 ? <p className="rounded-xl border border-dashed border-black/15 p-5 text-sm leading-6 text-stone-500">Your generated studies will appear here and remain private to your account.</p> : studies.map((study) => <Link key={study.id} to={`/bible-studies/${study.id}`} className="rounded-xl border border-black/10 bg-paper p-4 transition hover:border-moss/50"><span className="text-xs font-bold uppercase tracking-[0.12em] text-moss">{study.depth_label}</span><span className="mt-1 block font-semibold">{study.title}</span><span className="mt-2 block text-xs text-stone-500">{study.scripture_reference} · {study.translation}</span></Link>)}</div></aside>
                </div>
            )}

            {source && <p className="mt-8 text-xs leading-5 text-stone-500">Scripture: {source.name} ({source.edition}), {source.license}. <a className="font-semibold text-moss hover:underline" href={source.url} target="_blank" rel="noreferrer">Source and attribution</a>. AI sections are commentary, not Scripture.</p>}
        </AppShell>
    );
}
