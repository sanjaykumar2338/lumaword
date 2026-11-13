import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router-dom';
import { AppShell } from '../components/AppShell';
import { ApiError, apiRequest } from '../lib/api';
import type { BibleBook, BibleChapter, BiblePassage, BibleSearchResult, BibleTranslation } from '../types';

type DataResponse<T> = { data: T[] };

export function BiblePage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [translations, setTranslations] = useState<BibleTranslation[]>([]);
    const [books, setBooks] = useState<BibleBook[]>([]);
    const [chapters, setChapters] = useState<BibleChapter[]>([]);
    const [passage, setPassage] = useState<BiblePassage | null>(null);
    const [translation, setTranslation] = useState(searchParams.get('translation') ?? 'WEB');
    const [book, setBook] = useState(searchParams.get('book') ?? 'GEN');
    const [chapter, setChapter] = useState(Number(searchParams.get('chapter') ?? 1));
    const [selectedVerses, setSelectedVerses] = useState<number[]>([]);
    const [query, setQuery] = useState(searchParams.get('reference') ?? '');
    const [results, setResults] = useState<BibleSearchResult[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');

    const currentChapterIndex = useMemo(() => chapters.findIndex((item) => item.number === chapter), [chapters, chapter]);

    useEffect(() => {
        apiRequest<DataResponse<BibleTranslation>>('/api/v1/bible/translations')
            .then((response) => setTranslations(response.data))
            .catch(() => setError('We could not load Bible translations. Please try again.'));
    }, []);

    useEffect(() => {
        setIsLoading(true);
        apiRequest<DataResponse<BibleBook>>(`/api/v1/bible/books?translation=${encodeURIComponent(translation)}`)
            .then((response) => {
                setBooks(response.data);
                if (!response.data.some((item) => item.id === book)) setBook(response.data[0]?.id ?? 'GEN');
            })
            .catch(() => setError('We could not load the Bible books. Please try again.'))
            .finally(() => setIsLoading(false));
    }, [translation]);

    useEffect(() => {
        apiRequest<DataResponse<BibleChapter>>(`/api/v1/bible/${book}/chapters?translation=${encodeURIComponent(translation)}`)
            .then((response) => {
                setChapters(response.data);
                if (!response.data.some((item) => item.number === chapter)) setChapter(response.data[0]?.number ?? 1);
            })
            .catch(() => setError('We could not load chapters for this book. Please try again.'));
    }, [translation, book]);

    useEffect(() => {
        setIsLoading(true);
        apiRequest<{ data: BiblePassage }>(`/api/v1/bible/${book}/chapters/${chapter}?translation=${encodeURIComponent(translation)}`)
            .then((response) => {
                setPassage(response.data);
                setSelectedVerses([]);
                setSearchParams({ translation, book, chapter: String(chapter) }, { replace: true });
            })
            .catch((requestError) => setError(requestError instanceof ApiError ? requestError.message : 'We could not load this passage.'))
            .finally(() => setIsLoading(false));
    }, [translation, book, chapter]);

    useEffect(() => {
        const reference = searchParams.get('reference');
        if (!reference) return;
        apiRequest<{ data: BibleSearchResult[] }>(`/api/v1/bible/search?translation=${encodeURIComponent(translation)}&q=${encodeURIComponent(reference)}`)
            .then((response) => {
                const first = response.data[0];
                if (first) {
                    setBook(first.book.id);
                    setChapter(first.chapter);
                }
            })
            .catch(() => setError('We could not open that Scripture reference.'));
    }, []);

    const selectedText = passage?.verses.filter((verse) => selectedVerses.includes(verse.number)).map((verse) => `${verse.reference} ${verse.text}`).join('\n') ?? '';

    const search = async (event: FormEvent) => {
        event.preventDefault();
        if (!query.trim()) return;
        setError('');
        try {
            const response = await apiRequest<{ data: BibleSearchResult[] }>(`/api/v1/bible/search?translation=${encodeURIComponent(translation)}&q=${encodeURIComponent(query.trim())}`);
            setResults(response.data);
        } catch (requestError) {
            setError(requestError instanceof ApiError ? requestError.message : 'Search is temporarily unavailable.');
        }
    };

    const copy = async () => {
        if (!selectedText) return;
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(selectedText);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = selectedText;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
        }
        setNotice('Selected verses copied.');
        window.setTimeout(() => setNotice(''), 2500);
    };

    const openStudy = () => {
        if (!passage || selectedVerses.length === 0) return;
        const verses = passage.verses.filter((verse) => selectedVerses.includes(verse.number));
        const params = new URLSearchParams({
            translation,
            book,
            chapter: String(chapter),
            verse_start: String(verses[0].number),
            verse_end: String(verses[verses.length - 1].number),
        });
        window.location.assign(`/bible-studies?reader=${encodeURIComponent(params.toString())}`);
    };

    return (
        <AppShell>
            <header className="border-b border-black/10 pb-8"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Bible reader</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Read Scripture in full.</h1><p className="mt-3 max-w-3xl text-stone-600">All 66 books are available in three public-domain translations. Select verses to copy them or open a focused AI Bible Study.</p></header>
            {error && <div role="alert" className="mt-6 rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">{error}</div>}
            {notice && <div role="status" className="mt-6 rounded-2xl border border-moss/30 bg-white p-4 text-sm text-moss">{notice}</div>}

            <section className="mt-8 rounded-[1.75rem] bg-ink p-5 text-white sm:p-7">
                <div className="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto]">
                    <label className="text-sm font-semibold">Translation<select value={translation} onChange={(event) => setTranslation(event.target.value)} className="mt-2 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-white">{translations.map((item) => <option key={item.abbreviation} value={item.abbreviation} className="text-ink">{item.abbreviation} · {item.name}</option>)}</select></label>
                    <label className="text-sm font-semibold">Book<select value={book} onChange={(event) => setBook(event.target.value)} className="mt-2 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-white">{books.map((item) => <option key={item.id} value={item.id} className="text-ink">{item.name}</option>)}</select></label>
                    <label className="text-sm font-semibold">Chapter<select value={chapter} onChange={(event) => setChapter(Number(event.target.value))} className="mt-2 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-white">{chapters.map((item) => <option key={item.number} value={item.number} className="text-ink">{item.number}</option>)}</select></label>
                    <div className="flex items-end gap-2"><button type="button" disabled={currentChapterIndex <= 0} onClick={() => setChapter(chapters[currentChapterIndex - 1].number)} className="rounded-xl border border-white/15 px-4 py-3 text-sm font-semibold disabled:opacity-40">Previous</button><button type="button" disabled={currentChapterIndex < 0 || currentChapterIndex >= chapters.length - 1} onClick={() => setChapter(chapters[currentChapterIndex + 1].number)} className="rounded-xl bg-acid px-4 py-3 text-sm font-bold text-ink disabled:opacity-40">Next</button></div>
                </div>
                <form onSubmit={search} className="mt-5 flex flex-col gap-2 sm:flex-row"><label className="sr-only" htmlFor="bible-search">Search Scripture</label><input id="bible-search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search a word or reference, e.g. John 3:16" className="min-w-0 grow rounded-xl border border-white/15 bg-white px-4 py-3 text-sm text-ink outline-none" /><button className="rounded-xl border border-acid px-5 py-3 text-sm font-bold text-acid hover:bg-acid hover:text-ink">Search</button></form>
            </section>

            {results.length > 0 && <section className="mt-6 rounded-2xl border border-black/10 bg-white p-5"><div className="flex items-center justify-between gap-3"><h2 className="font-semibold">Search results</h2><button type="button" onClick={() => setResults([])} className="text-sm text-stone-500 hover:text-ink">Clear</button></div><div className="mt-4 grid gap-2">{results.map((result) => <button key={`${result.reference}-${result.translation.abbreviation}`} type="button" onClick={() => { setBook(result.book.id); setChapter(result.chapter); setResults([]); }} className="rounded-xl border border-black/10 p-4 text-left hover:border-moss/50"><span className="font-semibold">{result.reference}</span><span className="mt-1 block text-sm leading-6 text-stone-600">{result.text}</span></button>)}</div></section>}

            {isLoading || !passage ? <div className="mt-8 grid min-h-64 place-items-center rounded-[1.75rem] border border-dashed border-black/15 text-sm text-stone-500">Loading Scripture...</div> : <section className="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]"><article className="rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-10"><div className="flex flex-wrap items-end justify-between gap-4 border-b border-black/10 pb-6"><div><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">{passage.translation.abbreviation} · {passage.translation.name}</p><h2 className="mt-2 text-3xl font-semibold tracking-[-0.04em]">{passage.book.name} {passage.chapter}</h2></div><p className="max-w-sm text-right text-xs leading-5 text-stone-500">{passage.translation.license}. <a className="font-semibold text-moss hover:underline" href={passage.translation.source_url} target="_blank" rel="noreferrer">Source and attribution</a>.</p></div><div className="mt-7 grid gap-2">{passage.verses.map((verse) => <button key={verse.number} type="button" aria-pressed={selectedVerses.includes(verse.number)} onClick={() => setSelectedVerses((current) => current.includes(verse.number) ? current.filter((number) => number !== verse.number) : [...current, verse.number])} className={`flex gap-4 rounded-xl p-3 text-left transition ${selectedVerses.includes(verse.number) ? 'bg-acid/40 ring-1 ring-moss/30' : 'hover:bg-white'}`}><span className="w-8 shrink-0 pt-1 text-right text-xs font-bold text-moss">{verse.number}</span><span className="font-serif text-lg leading-8 text-stone-800">{verse.text}</span></button>)}</div></article><aside className="h-fit rounded-[1.75rem] bg-white p-6 ring-1 ring-black/5"><p className="text-xs font-bold uppercase tracking-[0.18em] text-moss">Verse tools</p><p className="mt-3 text-sm leading-6 text-stone-600">Click verses to select them. Selected text stays local until you copy it or send the passage to Bible Study.</p><div className="mt-5 grid gap-2"><button type="button" disabled={!selectedText} onClick={copy} className="rounded-xl bg-ink px-4 py-3 text-sm font-bold text-white disabled:opacity-40">Copy selected verses</button><button type="button" disabled={selectedVerses.length === 0} onClick={openStudy} className="rounded-xl bg-acid px-4 py-3 text-sm font-bold text-ink disabled:opacity-40">Open in AI Bible Study</button></div>{selectedVerses.length > 0 && <p className="mt-4 text-xs text-stone-500">{selectedVerses.length} verse{selectedVerses.length === 1 ? '' : 's'} selected.</p>}</aside></section>}
        </AppShell>
    );
}
