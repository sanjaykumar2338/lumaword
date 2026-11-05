import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { ApiError, apiRequest } from '../lib/api';

type Banner = {
    id: number; title: string; description: string; destination_url: string; image_url: string | null;
    image_alt: string | null; placement: string; is_enabled: boolean; starts_at: string | null; ends_at: string | null;
};

type BannerForm = Omit<Banner, 'id' | 'image_url' | 'image_alt' | 'starts_at' | 'ends_at'> & {
    image_url: string; image_alt: string; starts_at: string; ends_at: string;
};

const blank: BannerForm = {
    title: '', description: '', destination_url: '', image_url: '', image_alt: '', placement: 'dashboard',
    is_enabled: false, starts_at: '', ends_at: '',
};

export function BannerManager() {
    const [banners, setBanners] = useState<Banner[]>([]);
    const [form, setForm] = useState<BannerForm>(blank);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(true);
    const [working, setWorking] = useState(false);

    const load = useCallback(async () => {
        const response = await apiRequest<{ data: Banner[] }>('/api/v1/admin/promotional-banners');
        setBanners(response.data);
    }, []);

    useEffect(() => {
        load().catch(() => setError('Promotional banners could not be loaded.')).finally(() => setLoading(false));
    }, [load]);

    const save = async (event: FormEvent) => {
        event.preventDefault(); setMessage(''); setError(''); setWorking(true);
        const payload = { ...form, image_url: form.image_url || null, image_alt: form.image_alt || null, starts_at: form.starts_at || null, ends_at: form.ends_at || null };
        try {
            await apiRequest(editingId ? `/api/v1/admin/promotional-banners/${editingId}` : '/api/v1/admin/promotional-banners', {
                method: editingId ? 'PATCH' : 'POST', body: JSON.stringify(payload),
            });
            setForm(blank); setEditingId(null); setMessage(editingId ? 'Banner updated.' : 'Banner created.'); await load();
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Banner could not be saved.');
        } finally { setWorking(false); }
    };

    const edit = (banner: Banner) => {
        setEditingId(banner.id);
        setForm({
            title: banner.title, description: banner.description, destination_url: banner.destination_url,
            image_url: banner.image_url ?? '', image_alt: banner.image_alt ?? '', placement: banner.placement,
            is_enabled: banner.is_enabled, starts_at: toLocalInput(banner.starts_at), ends_at: toLocalInput(banner.ends_at),
        });
        setMessage(''); setError('');
    };

    const toggle = async (banner: Banner) => {
        setError('');
        try {
            await apiRequest(`/api/v1/admin/promotional-banners/${banner.id}`, { method: 'PATCH', body: JSON.stringify({ is_enabled: !banner.is_enabled }) });
            await load();
        } catch (caught) { setError(caught instanceof ApiError ? caught.message : 'Banner status could not be changed.'); }
    };

    const remove = async (banner: Banner) => {
        if (!window.confirm(`Remove “${banner.title}”?`)) return;
        setError('');
        try {
            await apiRequest(`/api/v1/admin/promotional-banners/${banner.id}`, { method: 'DELETE' });
            if (editingId === banner.id) { setEditingId(null); setForm(blank); }
            setMessage('Banner removed.'); await load();
        } catch (caught) { setError(caught instanceof ApiError ? caught.message : 'Banner could not be removed.'); }
    };

    return <section className="mt-6 rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-8" aria-labelledby="banner-manager-heading">
        <p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Promotional banners</p>
        <h2 id="banner-manager-heading" className="mt-2 text-2xl font-semibold">First-party promotions</h2>
        <p className="mt-2 text-sm text-stone-600">Manage approved HTTPS destinations and optional HTTPS images. Premium members remain ad-free.</p>
        {message && <p role="status" className="mt-4 rounded-xl bg-lime-100 px-4 py-3 text-sm text-moss">{message}</p>}
        {error && <p role="alert" className="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
        <div className="mt-6 grid gap-6 xl:grid-cols-[1fr_0.8fr]">
            <form onSubmit={save} className="grid gap-4 sm:grid-cols-2">
                <label className="grid gap-2 text-sm font-semibold">Title<input required maxLength={255} value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal" /></label>
                <label className="grid gap-2 text-sm font-semibold">Destination URL<input required type="url" placeholder="https://example.com" value={form.destination_url} onChange={(event) => setForm({ ...form, destination_url: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal" /></label>
                <label className="grid gap-2 text-sm font-semibold sm:col-span-2">Description<textarea required maxLength={1000} value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} className="min-h-24 rounded-xl border border-stone-300 bg-white p-3 font-normal" /></label>
                <label className="grid gap-2 text-sm font-semibold">Image URL<input type="url" placeholder="Optional HTTPS image URL" value={form.image_url} onChange={(event) => setForm({ ...form, image_url: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal" /></label>
                <label className="grid gap-2 text-sm font-semibold">Image alternative text<input value={form.image_alt} onChange={(event) => setForm({ ...form, image_alt: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal" /></label>
                <label className="grid gap-2 text-sm font-semibold">Placement<select value={form.placement} onChange={(event) => setForm({ ...form, placement: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal"><option value="dashboard">Dashboard</option><option value="sermon_library">Sermon library</option><option value="settings">Settings</option></select></label>
                <label className="flex items-end gap-3 pb-2 text-sm font-semibold"><input type="checkbox" checked={form.is_enabled} onChange={(event) => setForm({ ...form, is_enabled: event.target.checked })} className="size-5 accent-moss" />Enabled</label>
                <label className="grid gap-2 text-sm font-semibold">Starts at<input type="datetime-local" value={form.starts_at} onChange={(event) => setForm({ ...form, starts_at: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal" /></label>
                <label className="grid gap-2 text-sm font-semibold">Ends at<input type="datetime-local" value={form.ends_at} onChange={(event) => setForm({ ...form, ends_at: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal" /></label>
                <div className="flex flex-wrap gap-2 sm:col-span-2"><button disabled={working} className="h-11 rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss disabled:opacity-60">{working ? 'Saving…' : editingId ? 'Update banner' : 'Create banner'}</button>{editingId && <button type="button" onClick={() => { setEditingId(null); setForm(blank); }} className="h-11 rounded-xl border border-stone-300 px-5 text-sm font-semibold">Cancel edit</button>}</div>
            </form>
            <div><p className="text-xs font-bold uppercase tracking-wider text-stone-500">Preview</p><article className="mt-3 overflow-hidden rounded-2xl border border-black/10 bg-white">{form.image_url && <img src={form.image_url} alt={form.image_alt} className="h-36 w-full object-cover" />}<div className="p-5"><span className="text-xs font-bold uppercase text-moss">{form.placement.replaceAll('_', ' ')}</span><h3 className="mt-2 text-xl font-semibold">{form.title || 'Banner title'}</h3><p className="mt-2 text-sm leading-6 text-stone-600">{form.description || 'Banner description will appear here.'}</p><span className="mt-4 inline-flex rounded-lg bg-acid px-4 py-2 text-sm font-bold">Open promotion</span></div></article><p className="mt-3 text-xs text-stone-500">Preview only. The destination opens only from the published banner.</p></div>
        </div>
        <div className="mt-8"><h3 className="font-semibold">Saved banners</h3>{loading ? <p className="mt-4 text-sm text-stone-500">Loading banners…</p> : banners.length === 0 ? <p className="mt-4 rounded-xl border border-dashed border-stone-300 p-5 text-sm text-stone-500">No promotional banners have been created.</p> : <div className="mt-4 grid gap-3">{banners.map((banner) => <div key={banner.id} className="flex flex-col justify-between gap-4 rounded-xl border border-black/10 bg-white p-4 sm:flex-row sm:items-center"><div><strong className="block">{banner.title}</strong><span className="text-xs text-stone-500">{banner.placement.replaceAll('_', ' ')} · {banner.is_enabled ? 'enabled' : 'disabled'}</span></div><div className="flex flex-wrap gap-2"><button type="button" onClick={() => edit(banner)} className="rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold">Edit</button><button type="button" onClick={() => toggle(banner)} className="rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold">{banner.is_enabled ? 'Disable' : 'Enable'}</button><button type="button" onClick={() => remove(banner)} className="rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700">Remove</button></div></div>)}</div>}</div>
    </section>;
}

function toLocalInput(value: string | null): string {
    if (!value) return '';
    const date = new Date(value);
    const offset = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}
