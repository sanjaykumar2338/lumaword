import { useEffect, useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { ApiError, apiRequest } from '../lib/api';
import type { ResourceResponse, Sermon, YoutubeConnectionStatus } from '../types';

type SourceType = 'url' | 'transcript' | 'upload';

const methods: Array<{ id: SourceType; label: string; hint: string }> = [
    { id: 'url', label: 'Sermon link', hint: 'Paste a YouTube video link. Luma can retrieve captions only from a connected channel you own; other links stay saved for the fallback options.' },
    { id: 'transcript', label: 'Paste transcript', hint: 'The fastest path: paste the sermon text and Luma will queue the notes.' },
    { id: 'upload', label: 'Upload media', hint: 'Upload an authorized audio or video file for private transcription.' },
];

export function SermonSubmissionForm({ compact = false }: { compact?: boolean }) {
    const [sourceType, setSourceType] = useState<SourceType>('url');
    const [title, setTitle] = useState('');
    const [sourceUrl, setSourceUrl] = useState('');
    const [transcript, setTranscript] = useState('');
    const [media, setMedia] = useState<File | null>(null);
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [youtubeConnection, setYoutubeConnection] = useState<YoutubeConnectionStatus | null>(null);
    const [connectingYoutube, setConnectingYoutube] = useState(false);
    const navigate = useNavigate();
    const isYoutubeLink = /^(?:https?:\/\/)?(?:www\.|m\.|music\.)?(?:youtube\.com|youtu\.be)\//i.test(sourceUrl);

    useEffect(() => {
        apiRequest<YoutubeConnectionStatus>('/api/v1/youtube/connection')
            .then(setYoutubeConnection)
            .catch(() => setYoutubeConnection(null));
    }, []);

    const connectYoutube = async () => {
        setConnectingYoutube(true);
        setError('');

        try {
            const response = await apiRequest<{ authorization_url: string }>('/api/v1/youtube/connection', { method: 'POST' });
            window.location.assign(response.authorization_url);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'YouTube could not be connected. Please try again.');
            setConnectingYoutube(false);
        }
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setError('');
        setSubmitting(true);

        try {
            let body: BodyInit;

            if (sourceType === 'upload') {
                const form = new FormData();
                form.append('source_type', sourceType);
                form.append('title', title);
                form.append('auto_process', '1');
                if (media) form.append('media', media);
                body = form;
            } else {
                body = JSON.stringify({
                    source_type: sourceType,
                    title: title || null,
                    source_url: sourceType === 'url' ? sourceUrl : null,
                    transcript: sourceType === 'transcript' ? transcript : null,
                    auto_process: true,
                });
            }

            const response = await apiRequest<ResourceResponse<Sermon>>('/api/v1/sermons', { method: 'POST', body });
            navigate(`/sermons/${response.data.id}`);
        } catch (caught) {
            if (caught instanceof ApiError) {
                setError(Object.values(caught.errors)[0]?.[0] ?? caught.message);
            } else {
                setError('The sermon could not be submitted. Please try again.');
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form onSubmit={submit} className={`rounded-[1.75rem] border border-black/10 bg-paper ${compact ? 'p-5 sm:p-6' : 'p-6 sm:p-8'}`}>
            <div className="grid grid-cols-3 gap-2 rounded-2xl bg-stone-200/70 p-1" role="tablist" aria-label="Sermon source">
                {methods.map((method) => (
                    <button
                        key={method.id}
                        type="button"
                        role="tab"
                        aria-selected={sourceType === method.id}
                        onClick={() => { setSourceType(method.id); setError(''); }}
                        className={`rounded-xl px-2 py-2.5 text-xs font-semibold transition sm:text-sm ${sourceType === method.id ? 'bg-white text-ink shadow-sm' : 'text-stone-600 hover:text-ink'}`}
                    >
                        {method.label}
                    </button>
                ))}
            </div>

            <p className="mt-4 text-sm leading-6 text-stone-600">{methods.find((method) => method.id === sourceType)?.hint}</p>

            <div className="mt-5 grid gap-4">
                <label className="grid gap-2 text-sm font-semibold">
                    Title <span className="font-normal text-stone-500">(optional)</span>
                    <input value={title} onChange={(event) => setTitle(event.target.value)} maxLength={180} placeholder="Sunday sermon title" className="h-12 rounded-xl border border-stone-300 bg-white px-4 font-normal outline-none transition focus:border-moss focus:ring-2 focus:ring-acid/50" />
                </label>

                {sourceType === 'url' && (
                    <>
                        <label className="grid gap-2 text-sm font-semibold">
                            Public sermon URL
                            <input type="url" required value={sourceUrl} onChange={(event) => setSourceUrl(event.target.value)} placeholder="https://..." className="h-12 rounded-xl border border-stone-300 bg-white px-4 font-normal outline-none transition focus:border-moss focus:ring-2 focus:ring-acid/50" />
                        </label>
                        {isYoutubeLink && youtubeConnection?.connected ? (
                            <div className="rounded-xl border border-lime-300 bg-lime-50 px-4 py-3 text-xs leading-5 text-moss">
                                <strong>{youtubeConnection.channel?.title}</strong> is connected. If this channel owns the video and YouTube provides a downloadable caption, Luma will retrieve it and create notes automatically.
                            </div>
                        ) : isYoutubeLink && youtubeConnection?.configured ? (
                            <div className="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-950 sm:flex-row sm:items-center sm:justify-between">
                                <span>Automatic captions require the YouTube channel that owns this video.</span>
                                <button type="button" onClick={connectYoutube} disabled={connectingYoutube} className="shrink-0 rounded-full bg-ink px-4 py-2 font-semibold text-white disabled:opacity-60">{connectingYoutube ? 'Connecting...' : 'Connect your channel'}</button>
                            </div>
                        ) : (
                            <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-950">The link will stay attached to the sermon. If an authorized caption cannot be retrieved, you can paste a transcript or upload audio or video you are allowed to use.</p>
                        )}
                    </>
                )}

                {sourceType === 'transcript' && (
                    <label className="grid gap-2 text-sm font-semibold">
                        Sermon transcript
                        <textarea required minLength={200} value={transcript} onChange={(event) => setTranscript(event.target.value)} rows={compact ? 7 : 12} placeholder="Paste the sermon transcript here..." className="rounded-xl border border-stone-300 bg-white px-4 py-3 font-normal leading-6 outline-none transition focus:border-moss focus:ring-2 focus:ring-acid/50" />
                        <span className="text-right text-xs font-normal text-stone-500">{transcript.length.toLocaleString()} characters</span>
                    </label>
                )}

                {sourceType === 'upload' && (
                    <label className="grid gap-2 text-sm font-semibold">
                        Authorized audio or video
                        <input required type="file" accept=".flac,.mp3,.mp4,.mpeg,.mpga,.m4a,.ogg,.wav,.webm,audio/*,video/mp4,video/webm" onChange={(event) => setMedia(event.target.files?.[0] ?? null)} className="rounded-xl border border-dashed border-stone-400 bg-white px-4 py-7 font-normal file:mr-4 file:rounded-full file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                        <span className="text-xs font-normal text-stone-500">Private storage. Default maximum: 25 MB and 2 hours; administrators can configure both limits.</span>
                    </label>
                )}
            </div>

            {error && <p role="alert" className="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}

            <button disabled={submitting} className="mt-5 inline-flex h-12 w-full items-center justify-center rounded-xl bg-ink px-6 text-sm font-semibold text-white transition hover:bg-moss disabled:cursor-wait disabled:opacity-60">
                {submitting ? 'Submitting...' : sourceType === 'url' ? 'Save sermon link' : 'Submit and create notes'}
            </button>
        </form>
    );
}
