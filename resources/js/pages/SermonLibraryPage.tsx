import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { AppShell } from '../components/AppShell';
import { SermonStatusBadge } from '../components/SermonStatusBadge';
import { apiRequest } from '../lib/api';
import type { PaginatedResponse, Sermon } from '../types';

export function SermonLibraryPage() {
    const [sermons, setSermons] = useState<Sermon[]>([]);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(true);

    const load = (requestedPage = page) => {
        setLoading(true);
        const query = new URLSearchParams({ page: String(requestedPage), per_page: '12' });
        if (search) query.set('search', search);
        if (status) query.set('status', status);
        apiRequest<PaginatedResponse<Sermon>>(`/api/v1/sermons?${query}`)
            .then((response) => { setSermons(response.data); setPage(response.meta.current_page); setLastPage(response.meta.last_page); })
            .finally(() => setLoading(false));
    };

    useEffect(() => { load(1); }, []);

    const filter = (event: FormEvent) => { event.preventDefault(); load(1); };

    return (
        <AppShell>
            <header className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Your study archive</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Sermon library</h1></div>
                <Link to="/sermons/new" className="inline-flex h-11 items-center justify-center rounded-full bg-ink px-5 text-sm font-semibold text-white">Add a sermon</Link>
            </header>

            <form onSubmit={filter} className="mt-8 grid gap-3 rounded-2xl border border-black/10 bg-paper p-4 sm:grid-cols-[1fr_220px_auto]">
                <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search sermon titles" className="h-11 rounded-xl border border-stone-300 bg-white px-4 text-sm outline-none focus:border-moss" />
                <select value={status} onChange={(event) => setStatus(event.target.value)} className="h-11 rounded-xl border border-stone-300 bg-white px-3 text-sm outline-none focus:border-moss">
                    <option value="">All statuses</option><option value="completed">Completed</option><option value="queued">Queued</option><option value="analyzing">Creating notes</option><option value="transcribing">Transcribing</option><option value="awaiting_transcript">Needs transcript</option><option value="failed">Needs attention</option>
                </select>
                <button className="h-11 rounded-xl bg-ink px-5 text-sm font-semibold text-white">Filter</button>
            </form>

            <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {!loading && sermons.length === 0 && <div className="col-span-full rounded-2xl border border-dashed border-stone-400 bg-paper p-10 text-center"><h2 className="text-xl font-semibold">No sermons found</h2><p className="mt-2 text-sm text-stone-600">Try another filter or add your first sermon.</p></div>}
                {sermons.map((sermon) => (
                    <Link key={sermon.id} to={`/sermons/${sermon.id}`} className="flex min-h-52 flex-col rounded-2xl border border-black/10 bg-paper p-6 transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div className="flex items-start justify-between gap-3"><span className="text-xs font-bold uppercase tracking-wider text-stone-500">{sermon.source_provider ?? sermon.source_type}</span><SermonStatusBadge status={sermon.processing_status} /></div>
                        <h2 className="mt-auto pt-8 text-xl font-semibold tracking-[-0.03em]">{sermon.title}</h2><p className="mt-2 text-sm text-stone-500">{new Date(sermon.created_at).toLocaleDateString()}</p>
                    </Link>
                ))}
            </div>

            {lastPage > 1 && <div className="mt-8 flex items-center justify-center gap-3"><button type="button" disabled={page <= 1} onClick={() => load(page - 1)} className="rounded-full border border-stone-300 px-4 py-2 text-sm disabled:opacity-40">Previous</button><span className="text-sm text-stone-600">Page {page} of {lastPage}</span><button type="button" disabled={page >= lastPage} onClick={() => load(page + 1)} className="rounded-full border border-stone-300 px-4 py-2 text-sm disabled:opacity-40">Next</button></div>}
        </AppShell>
    );
}
