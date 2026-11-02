import { useEffect, useState } from 'react';
import { apiRequest } from '../lib/api';

type Banner = { id: number; title: string; description: string; image_url: string | null; image_alt: string | null; destination_url: string };

export function PromotionalBanners({ placement }: { placement: 'dashboard' | 'sermon_library' | 'settings' }) {
    const [banners, setBanners] = useState<Banner[]>([]);

    useEffect(() => {
        apiRequest<{ data: Banner[] }>(`/api/v1/promotional-banners?placement=${placement}`)
            .then((response) => setBanners(response.data)).catch(() => setBanners([]));
    }, [placement]);

    if (banners.length === 0) return null;

    return <aside className="mt-8 grid gap-3" aria-label="Luma promotions">{banners.map((banner) => <a key={banner.id} href={banner.destination_url} target="_blank" rel="noreferrer" className="grid gap-4 rounded-2xl border border-black/10 bg-white p-4 transition hover:border-moss sm:grid-cols-[auto_1fr] sm:items-center">{banner.image_url && <img src={banner.image_url} alt={banner.image_alt ?? ''} className="h-20 w-full rounded-xl object-cover sm:w-32" />}<span><span className="block text-xs font-bold uppercase tracking-widest text-moss">Sponsored by Luma</span><strong className="mt-1 block text-lg">{banner.title}</strong><span className="mt-1 block text-sm text-stone-600">{banner.description}</span></span></a>)}</aside>;
}
