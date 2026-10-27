import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { AppShell } from '../components/AppShell';
import { BibleStudyView } from '../components/BibleStudyView';
import { ApiError, apiRequest } from '../lib/api';
import type { BibleStudy, ResourceResponse } from '../types';

export function BibleStudyDetailPage() {
    const { studyId } = useParams();
    const [study, setStudy] = useState<BibleStudy | null>(null);
    const [notes, setNotes] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [status, setStatus] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

    useEffect(() => {
        apiRequest<ResourceResponse<BibleStudy>>(`/api/v1/bible-studies/${studyId}`)
            .then((response) => { setStudy(response.data); setNotes(response.data.personal_notes ?? ''); })
            .catch((error) => setStatus({ type: 'error', message: error instanceof ApiError && error.status === 403 ? 'This private study belongs to another account.' : 'We could not reopen this study.' }))
            .finally(() => setIsLoading(false));
    }, [studyId]);

    const saveNotes = async () => {
        if (!study) return;
        setIsSaving(true);
        setStatus(null);
        try {
            const response = await apiRequest<ResourceResponse<BibleStudy>>(`/api/v1/bible-studies/${study.id}`, { method: 'PATCH', body: JSON.stringify({ personal_notes: notes || null }) });
            setStudy(response.data);
            setNotes(response.data.personal_notes ?? '');
            setStatus({ type: 'success', message: 'Your personal notes were saved.' });
        } catch (error) {
            setStatus({ type: 'error', message: error instanceof ApiError ? error.message : 'Your notes could not be saved. Please try again.' });
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <AppShell>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3"><Link to="/bible-studies" className="text-sm font-semibold text-moss hover:underline">← Back to study library</Link>{study && <span className="text-xs text-stone-500">Generated {new Date(study.generated_at).toLocaleDateString()}</span>}</div>
            {status?.type === 'error' && <div role="alert" className="mb-6 rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">{status.message}</div>}
            {isLoading ? <div className="grid min-h-64 place-items-center rounded-[1.75rem] border border-dashed border-black/15 text-sm text-stone-500">Opening study...</div> : study ? <><BibleStudyView study={study} /><section className="mt-5 rounded-[1.5rem] border border-black/10 bg-white/70 p-5 sm:p-7"><div className="flex flex-wrap items-start justify-between gap-3"><div><h2 className="text-2xl font-semibold tracking-[-0.035em]">My notes</h2><p className="mt-1 text-sm text-stone-500">Private reflections are stored separately from Luma's AI-generated explanation.</p></div>{status?.type === 'success' && <p role="status" className="text-sm font-medium text-moss">{status.message}</p>}</div><label htmlFor="personal-notes" className="sr-only">Personal study notes</label><textarea id="personal-notes" value={notes} onChange={(event) => setNotes(event.target.value)} maxLength={20000} rows={8} placeholder="Write observations, questions, or reflections..." className="mt-5 w-full rounded-xl border border-stone-300 bg-paper px-4 py-3 text-sm leading-6 placeholder:text-stone-400" /><div className="mt-3 flex items-center justify-between gap-3"><span className="text-xs text-stone-500">{notes.length.toLocaleString()} / 20,000</span><button type="button" onClick={saveNotes} disabled={isSaving} className="rounded-xl bg-moss px-5 py-3 text-sm font-bold text-white transition hover:bg-ink disabled:cursor-wait disabled:opacity-60">{isSaving ? 'Saving...' : 'Save my notes'}</button></div></section><p className="mt-5 text-xs leading-5 text-stone-500">Scripture is World English Bible (WEB), Public Domain. All other sections are AI-generated commentary and may reflect scholarly uncertainty; verify important claims with trusted sources.</p></> : <div className="rounded-[1.75rem] border border-dashed border-black/15 p-8 text-center"><h1 className="text-xl font-semibold">Study unavailable</h1><p className="mt-2 text-sm text-stone-500">Return to your library to choose another saved study.</p></div>}
        </AppShell>
    );
}
