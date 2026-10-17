import { AppShell } from '../components/AppShell';
import { SermonSubmissionForm } from '../components/SermonSubmissionForm';
import { useSearchParams } from 'react-router-dom';

export function NewSermonPage() {
    const [searchParams] = useSearchParams();
    const youtubeResult = searchParams.get('youtube');

    return (
        <AppShell>
            <header className="max-w-3xl"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">New sermon</p><h1 className="mt-3 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Choose the simplest way in.</h1><p className="mt-4 text-base leading-7 text-stone-600">Paste a transcript for the quickest result, save a supported public link, or upload media you are authorized to process.</p></header>
            {youtubeResult === 'connected' && <p className="mt-6 max-w-4xl rounded-xl bg-lime-100 px-4 py-3 text-sm text-moss">YouTube channel connected. Paste a video link from that channel and Luma can check for an authorized caption.</p>}
            {youtubeResult === 'connection_failed' && <p role="alert" className="mt-6 max-w-4xl rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">YouTube could not be connected. Please try again or use a pasted transcript or authorized media upload.</p>}
            <div className="mt-8 max-w-4xl"><SermonSubmissionForm /></div>
        </AppShell>
    );
}
