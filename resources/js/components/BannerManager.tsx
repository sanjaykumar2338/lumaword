import { useEffect, useState, type FormEvent } from 'react';
import { ApiError, apiRequest } from '../lib/api';

type Banner = { id: number; title: string; description: string; destination_url: string; image_url: string | null; image_alt: string | null; placement: string; is_enabled: boolean };
const blank = { title: '', description: '', destination_url: '', image_url: '', image_alt: '', placement: 'dashboard', is_enabled: false };

export function BannerManager() {
    const [banners, setBanners] = useState<Banner[]>([]);
    const [form, setForm] = useState(blank);
    const [message, setMessage] = useState('');

    const load = () => apiRequest<{ data: Banner[] }>('/api/v1/admin/promotional-banners').then((response) => setBanners(response.data));
    useEffect(() => { load().catch(() => setMessage('Promotional banners could not be loaded.')); }, []);

    const create = async (event: FormEvent) => {
        event.preventDefault(); setMessage('');
        try {
            await apiRequest('/api/v1/admin/promotional-banners', { method: 'POST', body: JSON.stringify({ ...form, image_url: form.image_url || null, image_alt: form.image_alt || null }) });
            setForm(blank); setMessage('Banner saved. It remains disabled until you enable it.'); await load();
        } catch (error) { setMessage(error instanceof ApiError ? error.message : 'Banner could not be saved.'); }
    };

    const toggle = async (banner: Banner) => {
        await apiRequest(`/api/v1/admin/promotional-banners/${banner.id}`, { method: 'PATCH', body: JSON.stringify({ is_enabled: !banner.is_enabled }) });
        await load();
    };

    return <section className="mt-6 rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-8"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Administrator</p><h2 className="mt-2 text-2xl font-semibold">First-party promotions</h2><p className="mt-2 text-sm text-stone-600">Create only approved brand content. New banners are disabled by default.</p>{message && <p role="status" className="mt-4 text-sm text-moss">{message}</p>}<form onSubmit={create} className="mt-6 grid gap-3 sm:grid-cols-2"><input required aria-label="Banner title" placeholder="Title" value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} className="h-11 rounded-xl border border-stone-300 px-3" /><input required type="url" aria-label="Destination URL" placeholder="https://store.example" value={form.destination_url} onChange={(event) => setForm({ ...form, destination_url: event.target.value })} className="h-11 rounded-xl border border-stone-300 px-3" /><textarea required aria-label="Banner description" placeholder="Description" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} className="min-h-24 rounded-xl border border-stone-300 p-3 sm:col-span-2" /><input type="url" aria-label="Image URL" placeholder="Optional HTTPS image URL" value={form.image_url} onChange={(event) => setForm({ ...form, image_url: event.target.value })} className="h-11 rounded-xl border border-stone-300 px-3" /><input aria-label="Image alternative text" placeholder="Image alternative text" value={form.image_alt} onChange={(event) => setForm({ ...form, image_alt: event.target.value })} className="h-11 rounded-xl border border-stone-300 px-3" /><button className="h-11 justify-self-start rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss">Save disabled banner</button></form><div className="mt-6 grid gap-2">{banners.map((banner) => <div key={banner.id} className="flex items-center justify-between gap-4 rounded-xl border border-black/10 bg-white p-4"><div><strong className="block">{banner.title}</strong><span className="text-xs text-stone-500">{banner.placement} · {banner.is_enabled ? 'enabled' : 'disabled'}</span></div><button type="button" onClick={() => toggle(banner)} className="rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold">{banner.is_enabled ? 'Disable' : 'Enable'}</button></div>)}</div></section>;
}
