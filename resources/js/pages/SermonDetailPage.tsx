import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { AppShell } from '../components/AppShell';
import { SermonStatusBadge } from '../components/SermonStatusBadge';
import { ApiError, apiRequest } from '../lib/api';
import type { ResourceResponse, Sermon } from '../types';

const activeStatuses = ['submitted', 'transcribing', 'queued', 'analyzing'];

export function SermonDetailPage() {
    const { sermonId } = useParams();
    const navigate = useNavigate();
    const [sermon, setSermon] = useState<Sermon | null>(null);
    const [transcript, setTranscript] = useState('');
    const [editingTranscript, setEditingTranscript] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [working, setWorking] = useState(false);

    const load = useCallback(async () => {
        const response = await apiRequest<ResourceResponse<Sermon>>(`/api/v1/sermons/${sermonId}`);
        setSermon(response.data);
        if (!editingTranscript) setTranscript(response.data.transcript ?? '');
    }, [sermonId, editingTranscript]);

    useEffect(() => { load().catch(() => setError('This sermon could not be loaded.')); }, [load]);

    useEffect(() => {
        if (!sermon || !activeStatuses.includes(sermon.processing_status)) return;
        const timer = window.setInterval(() => load().catch(() => undefined), 5000);
        return () => window.clearInterval(timer);
    }, [sermon?.processing_status, load]);

    const saveTranscript = async (event: FormEvent) => {
        event.preventDefault();
        setWorking(true); setError(''); setMessage('');
        try {
            const response = await apiRequest<ResourceResponse<Sermon>>(`/api/v1/sermons/${sermonId}/transcript`, {
                method: 'POST',
                body: JSON.stringify({ transcript, process: true }),
            });
            setSermon(response.data); setEditingTranscript(false); setMessage(response.message ?? 'Transcript saved.');
        } catch (caught) {
            setError(caught instanceof ApiError ? Object.values(caught.errors)[0]?.[0] ?? caught.message : 'The transcript could not be saved.');
        } finally { setWorking(false); }
    };

    const process = async () => {
        setWorking(true); setError(''); setMessage('');
        try {
            const response = await apiRequest<ResourceResponse<Sermon>>(`/api/v1/sermons/${sermonId}/process`, { method: 'POST' });
            setSermon(response.data); setMessage(response.message ?? 'Sermon queued.');
        } catch (caught) {
            setError(caught instanceof ApiError ? Object.values(caught.errors)[0]?.[0] ?? caught.message : 'The sermon could not be queued.');
        } finally { setWorking(false); }
    };

    const remove = async () => {
        if (!window.confirm('Delete this sermon, transcript, and notes? This cannot be undone.')) return;
        await apiRequest<void>(`/api/v1/sermons/${sermonId}`, { method: 'DELETE' });
        navigate('/sermons');
    };

    if (!sermon) return <AppShell><p className="text-sm text-stone-600">{error || 'Loading sermon...'}</p></AppShell>;

    const notes = sermon.notes;

    return (
        <AppShell>
            <div className="flex flex-wrap items-center justify-between gap-3"><Link to="/sermons" className="text-sm font-semibold text-stone-600 hover:text-ink">Back to library</Link><button type="button" onClick={remove} className="text-sm text-red-700 hover:underline">Delete sermon</button></div>

            <header className="mt-6 border-b border-black/10 pb-8">
                <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">{sermon.source_provider ?? sermon.source_type}</p><h1 className="mt-2 max-w-4xl text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">{sermon.title}</h1>{sermon.source_url && <a href={sermon.source_url} target="_blank" rel="noreferrer" className="mt-3 inline-flex text-sm font-semibold text-moss hover:underline">Open original source</a>}</div>
                    <SermonStatusBadge status={sermon.processing_status} />
                </div>
            </header>

            {message && <p className="mt-5 rounded-xl bg-lime-100 px-4 py-3 text-sm text-moss">{message}</p>}
            {error && <p role="alert" className="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
            {sermon.processing_status === 'awaiting_transcript' && (
                <section className="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5" aria-labelledby="transcript-needed-title">
                    <p className="text-xs font-bold uppercase tracking-[0.2em] text-amber-900">Transcript needed</p>
                    <h2 id="transcript-needed-title" className="mt-2 text-xl font-semibold">This link was saved, but no transcript was retrieved.</h2>
                    <p className="mt-2 max-w-3xl text-sm leading-6 text-amber-950">Paste a transcript below to create notes, or start a new sermon and upload audio or video that you own or have permission to use.</p>
                    <div className="mt-4 flex flex-wrap gap-3">
                        <button type="button" onClick={() => document.querySelector<HTMLTextAreaElement>('textarea')?.focus()} className="rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white">Paste transcript below</button>
                        <Link to="/sermons/new" className="rounded-full border border-amber-400 px-4 py-2 text-sm font-semibold text-amber-950">Upload authorized media</Link>
                    </div>
                </section>
            )}
            {sermon.processing_error && <div className="mt-5 flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-sm text-red-900">{sermon.processing_error}</p><button type="button" disabled={working} onClick={process} className="rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white">Retry processing</button></div>}

            {activeStatuses.includes(sermon.processing_status) && <section className="mt-6 rounded-2xl bg-ink p-6 text-white"><p className="text-xs font-bold uppercase tracking-[0.2em] text-acid">Working in the background</p><h2 className="mt-2 text-xl font-semibold">You can safely leave this page.</h2><p className="mt-2 text-sm leading-6 text-stone-400">Luma will keep processing and this page checks for updates automatically.</p></section>}

            <div className="mt-8 grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                <section className="rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-8">
                    <div className="flex items-center justify-between gap-3"><div><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">Source text</p><h2 className="mt-2 text-2xl font-semibold">Transcript</h2></div>{sermon.has_transcript && !editingTranscript && <button type="button" onClick={() => setEditingTranscript(true)} className="text-sm font-semibold text-moss hover:underline">Edit</button>}</div>

                    {(editingTranscript || !sermon.has_transcript) ? (
                        <form onSubmit={saveTranscript} className="mt-5"><textarea required minLength={200} value={transcript} onChange={(event) => setTranscript(event.target.value)} rows={18} placeholder="Paste an authorized sermon transcript here..." className="w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-moss" /><div className="mt-3 flex items-center justify-between gap-3"><span className="text-xs text-stone-500">{transcript.length.toLocaleString()} characters</span><div className="flex gap-2">{sermon.has_transcript && <button type="button" onClick={() => { setEditingTranscript(false); setTranscript(sermon.transcript ?? ''); }} className="rounded-full border border-stone-300 px-4 py-2 text-sm">Cancel</button>}<button disabled={working} className="rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white">Save & process</button></div></div></form>
                    ) : <div className="mt-5 max-h-[38rem] overflow-y-auto whitespace-pre-wrap rounded-xl bg-white p-5 text-sm leading-7 text-stone-700">{sermon.transcript}</div>}
                </section>

                <section className="grid content-start gap-5">
                    {!notes ? (
                        <div className="rounded-[1.75rem] border border-dashed border-stone-400 bg-paper p-8"><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">AI notes</p><h2 className="mt-3 text-2xl font-semibold">{sermon.processing_status === 'awaiting_transcript' ? 'Add a transcript to continue.' : 'Notes will appear here.'}</h2><p className="mt-3 text-sm leading-6 text-stone-600">Processing uses the actual transcript and never waits in the browser request.</p></div>
                    ) : (
                        <>
                            <article className="rounded-[1.75rem] bg-acid p-7"><p className="text-xs font-bold uppercase tracking-[0.2em] text-moss">Main idea</p><p className="mt-4 text-2xl font-semibold leading-snug tracking-[-0.035em]">{notes.main_idea}</p></article>
                            <article className="rounded-[1.75rem] border border-black/10 bg-paper p-7"><h2 className="text-xl font-semibold">Summary</h2><p className="mt-3 leading-7 text-stone-700">{notes.summary}</p></article>
                            <NoteList title="Outline" items={notes.outline.map((item) => `${item.heading}: ${item.details}`)} numbered />
                            <NoteList title="Key points" items={notes.key_points} />
                            <article className="rounded-[1.75rem] border border-black/10 bg-paper p-7"><h2 className="text-xl font-semibold">Scripture references</h2><div className="mt-4 grid gap-3">{notes.scripture_references.length === 0 ? <p className="text-sm text-stone-500">No Scripture references were identified.</p> : notes.scripture_references.map((reference, index) => <div key={`${reference.reference}-${index}`} className="rounded-xl bg-white p-4"><div className="flex flex-wrap items-center justify-between gap-2"><strong>{reference.reference}</strong><span className="text-xs text-stone-500">{reference.explicitly_mentioned ? 'Mentioned' : 'Suggested'} · {reference.verification_status}</span></div>{reference.context && <p className="mt-2 text-sm leading-6 text-stone-600">{reference.context}</p>}{reference.transcript_excerpt && <blockquote className="mt-2 border-l-2 border-acid pl-3 text-sm italic text-stone-600">{reference.transcript_excerpt}</blockquote>}</div>)}</div></article>
                            <NoteList title="Themes" items={notes.themes} />
                            <NoteList title="Practical applications" items={notes.applications} />
                            <NoteList title="Reflection & discussion" items={notes.discussion_questions} numbered />
                            <p className="px-2 text-xs text-stone-500">Generated with {notes.ai.provider} · {notes.ai.model} · prompt {notes.ai.prompt_version}</p>
                        </>
                    )}
                </section>
            </div>
        </AppShell>
    );
}

function NoteList({ title, items, numbered = false }: { title: string; items: string[]; numbered?: boolean }) {
    return <article className="rounded-[1.75rem] border border-black/10 bg-paper p-7"><h2 className="text-xl font-semibold">{title}</h2><div className="mt-4 grid gap-3">{items.map((item, index) => <div key={`${title}-${index}`} className="flex gap-3 rounded-xl bg-white p-4"><span className="grid size-7 shrink-0 place-items-center rounded-full bg-ink text-xs font-bold text-acid">{numbered ? index + 1 : '•'}</span><p className="text-sm leading-6 text-stone-700">{item}</p></div>)}</div></article>;
}
