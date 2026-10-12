import { AppShell } from '../components/AppShell';
import { SermonSubmissionForm } from '../components/SermonSubmissionForm';

export function NewSermonPage() {
    return (
        <AppShell>
            <header className="max-w-3xl"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">New sermon</p><h1 className="mt-3 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Choose the simplest way in.</h1><p className="mt-4 text-base leading-7 text-stone-600">Paste a transcript for the quickest result, save a supported public link, or upload media you are authorized to process.</p></header>
            <div className="mt-8 max-w-4xl"><SermonSubmissionForm /></div>
        </AppShell>
    );
}
