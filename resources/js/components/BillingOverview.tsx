import { useEffect, useState } from 'react';
import { ApiError, apiRequest } from '../lib/api';
import type { Entitlements, ResourceResponse } from '../types';

const labels: Record<string, string> = {
    sermon_processing: 'Sermons processed',
    ask_luma: 'Ask Luma questions',
    bible_study: 'Bible studies',
    devotional: 'Generated devotionals',
};

export function BillingOverview({ compact = false }: { compact?: boolean }) {
    const [data, setData] = useState<Entitlements | null>(null);
    const [error, setError] = useState('');
    const [working, setWorking] = useState(false);

    useEffect(() => {
        apiRequest<ResourceResponse<Entitlements>>('/api/v1/entitlements')
            .then((response) => setData(response.data))
            .catch(() => setError('Membership and usage could not be loaded.'));
    }, []);

    const openBilling = async (action: 'checkout' | 'portal', interval?: 'monthly' | 'annual') => {
        setWorking(true); setError('');
        try {
            const response = await apiRequest<{ url: string }>(`/api/v1/billing/${action}`, {
                method: 'POST', body: interval ? JSON.stringify({ interval }) : undefined,
            });
            window.location.assign(response.url);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Billing is unavailable. Please try again.');
            setWorking(false);
        }
    };

    if (!data && !error) return <p className="text-sm text-stone-500">Loading membership and usage…</p>;

    return <div className="grid gap-6">
        {error && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
        {data && <>
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Current membership</p><h2 className="mt-2 text-2xl font-semibold capitalize">{data.membership.plan}</h2><p className="mt-1 text-sm text-stone-600">Status: {data.membership.status.replaceAll('_', ' ')}</p></div>
                {data.membership.plan === 'premium'
                    ? <button disabled={working} onClick={() => openBilling('portal')} className="h-11 rounded-xl border border-stone-300 px-5 text-sm font-semibold hover:bg-stone-100 disabled:opacity-60">Manage billing & invoices</button>
                    : !compact && <div className="flex flex-wrap gap-2"><button disabled={working || !data.membership.billing_enabled} onClick={() => openBilling('checkout', 'monthly')} className="h-11 rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss disabled:opacity-50">Choose monthly</button><button disabled={working || !data.membership.billing_enabled} onClick={() => openBilling('checkout', 'annual')} className="h-11 rounded-xl bg-acid px-5 text-sm font-bold hover:bg-lime-300 disabled:opacity-50">Choose annual</button></div>}
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                {Object.entries(data.usage.features).map(([feature, usage]) => <div key={feature} className="rounded-xl border border-black/10 bg-white/60 p-4"><div className="flex justify-between gap-3 text-sm"><span className="font-semibold">{labels[feature] ?? feature}</span><span>{usage.used} of {usage.limit}</span></div><div className="mt-3 h-2 overflow-hidden rounded-full bg-stone-200"><div className="h-full rounded-full bg-moss" style={{ width: `${Math.min(100, usage.limit ? usage.used / usage.limit * 100 : 100)}%` }} /></div></div>)}
            </div>
            <p className="text-xs text-stone-500">Allowances reset {new Date(data.usage.resets_at).toLocaleString()} in your local timezone. Saved content and Bible reading remain available.</p>
        </>}
    </div>;
}
